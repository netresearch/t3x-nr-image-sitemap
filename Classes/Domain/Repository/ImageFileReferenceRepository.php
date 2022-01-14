<?php

/**
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Domain\Repository;

use Doctrine\DBAL\Driver\ResultStatement;
use Exception;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * The image sitemap repository.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class ImageFileReferenceRepository extends Repository
{
    /**
     * @var ConnectionPool
     */
    private ConnectionPool $connectionPool;

    /**
     * @var Context
     */
    private Context $context;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ConnectionPool $connectionPool
     * @param Context $context
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ConnectionPool $connectionPool,
        Context $context
    ) {
        parent::__construct($objectManager);

        $this->connectionPool = $connectionPool;
        $this->context = $context;
    }

    /**
     * Returns file references for given file types.
     *
     * @param int[] $fileTypes List of file types to return the file references
     *
     * @return null|QueryResultInterface
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws InvalidQueryException
     */
    public function findAllByType(array $fileTypes): ?QueryResultInterface
    {
        $statement       = $this->getAllRecordsByFileType($fileTypes);
        $existingRecords = [];

        // Walk result set row by row, to prevent too much memory usage
        while ($row = $statement->fetchAssociative()) {
            if (!isset($row['tablenames'], $row['uid_foreign'])) {
                continue;
            }

            // Check if the foreign table record exists
            if ($this->findRecordByForeignUid($row['tablenames'], $row['uid_foreign'])) {
                $existingRecords[] = (int) $row['uid'];
            }
        }

        // Remove duplicates
        $existingRecords = array_unique($existingRecords);

        if (empty($existingRecords)) {
            return null;
        }

        $query = $this->createQuery();

        // Return all records
        return $query
            ->matching(
                $query->in('uid', $existingRecords)
            )
            ->execute();
    }

    /**
     * Returns all file reference records matching the given list of file types.
     *
     * @param int[] $fileTypes List of file types to return the file references
     *
     * @return ResultStatement
     */
    private function getAllRecordsByFileType(array $fileTypes): ResultStatement
    {
        $connection   = $this->connectionPool->getConnectionForTable('sys_file_reference');
        $queryBuilder = $connection->createQueryBuilder();

        return $queryBuilder
            ->select('r.uid', 'r.uid_foreign', 'r.tablenames')
            ->from('sys_file_reference', 'r')
            ->leftJoin(
                'r',
                'sys_file',
                'f',
                $queryBuilder->expr()->eq('f.uid', $queryBuilder->quoteIdentifier('r.uid_local'))
            )
            ->leftJoin(
                'r',
                'pages',
                'p',
                $queryBuilder->expr()->eq('p.uid', $queryBuilder->quoteIdentifier('r.pid'))
            )
            ->andWhere(
                $queryBuilder->expr()->isNotNull('f.uid')
            )
            ->andWhere(
                $queryBuilder->expr()->eq('f.missing', 0)
            )
            ->andWhere(
                $queryBuilder->expr()->in('f.type', $fileTypes)
            )
            ->andWhere(
                $queryBuilder->expr()->eq('r.t3ver_wsid', 0)
            )
            ->andWhere(
                $queryBuilder->expr()->eq('r.sys_language_uid',
                    $queryBuilder->createNamedParameter(
                        $this->getLanguageUid(),
                        Connection::PARAM_INT
                    )
                )
            )
            ->execute();
    }

    /**
     * Returns the UID of the record the foreign table related to or FALSE otherwise.
     *
     * @param string $tableName  The foreign table to check
     * @param int    $foreignUid The foreign UID to check
     *
     * @return bool
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function findRecordByForeignUid(string $tableName, int $foreignUid): bool
    {
        $connection    = $this->connectionPool->getConnectionForTable($tableName);
        $schemaManager = $connection->getSchemaManager();

        // Table did not exist => abort
        if (!$schemaManager || !$schemaManager->tablesExist([ $tableName ])) {
            return false;
        }

        $queryBuilder = $connection->createQueryBuilder();

        return (bool) $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $foreignUid,
                        Connection::PARAM_INT
                    )
                )
            )
            ->execute()
            ->fetchOne();
    }

    /**
     * Returns the current language UID.
     *
     * @return int
     */
    private function getLanguageUid(): int
    {
        try {
            return $this->context->getPropertyFromAspect('language', 'id');
        } catch (Exception $exception) {
            return 0;
        }
    }
}
