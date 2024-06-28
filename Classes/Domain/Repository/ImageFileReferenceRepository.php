<?php

/**
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Domain\Repository;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\ResultStatement;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * The image file reference repository.
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
    private readonly ConnectionPool $connectionPool;

    /**
     * @var Context
     */
    private readonly Context $context;

    /**
     * Constructor.
     *
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
     * @param int[]    $fileTypes        List of file types to return the file references
     * @param int[]    $pageList         List of page IDs to include
     * @param string[] $tables           List of tables names used to filter the result
     * @param int[]    $excludedDoktypes List of excluded document types
     * @param string   $additionalWhere  Additional where clause
     *
     * @return null|QueryResultInterface
     *
     * @throws InvalidQueryException
     * @throws Exception
     */
    public function findAllImages(
        array $fileTypes,
        array $pageList,
        array $tables,
        array $excludedDoktypes = [],
        string $additionalWhere = ''
    ): ?QueryResultInterface {
        $statement       = $this->getAllRecords($fileTypes, $pageList, $tables, $excludedDoktypes, $additionalWhere);
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

        if ($existingRecords === []) {
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
     * Returns all file reference records.
     *
     * @param int[]    $fileTypes        List of file types to return the file references
     * @param int[]    $pageList         List of page IDs to include
     * @param string[] $tables           List of tables names used to filter the result
     * @param int[]    $excludedDoktypes List of excluded document types
     * @param string   $additionalWhere  Additional where clause
     *
     * @return ResultStatement
     */
    private function getAllRecords(
        array $fileTypes,
        array $pageList,
        array $tables,
        array $excludedDoktypes = [],
        string $additionalWhere = ''
    ): ResultStatement {
        $connection = $this->connectionPool->getConnectionForTable('sys_file_reference');

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('r.uid', 'r.uid_foreign', 'r.tablenames')
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
                $queryBuilder->expr()->in(
                    'p.uid',
                    $queryBuilder->createNamedParameter(
                        $pageList,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->andWhere(
                $queryBuilder->expr()->isNotNull('f.uid')
            )
            ->andWhere(
                $queryBuilder->expr()->eq('f.missing', 0)
            )
            ->andWhere(
                $queryBuilder->expr()->in(
                    'f.type',
                    $queryBuilder->createNamedParameter(
                        $fileTypes,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->andWhere(
                $queryBuilder->expr()->in(
                    'r.tablenames',
                    $queryBuilder->createNamedParameter(
                        $tables,
                        Connection::PARAM_STR_ARRAY
                    )
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq('r.t3ver_wsid', 0)
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'r.sys_language_uid',
                    $queryBuilder->createNamedParameter(
                        $this->getLanguageUid(),
                        Connection::PARAM_INT
                    )
                )
            );

        if ($excludedDoktypes !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'p.doktype',
                    $queryBuilder->createNamedParameter(
                        $excludedDoktypes,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            );
        }

        if ($additionalWhere !== '') {
            $queryBuilder->andWhere(
                QueryHelper::stripLogicalOperatorPrefix($additionalWhere)
            );
        }

        return $queryBuilder->execute();
    }

    /**
     * Returns the UID of the record the foreign table related to or FALSE otherwise.
     *
     * @param string $tableName  The foreign table to check
     * @param int    $foreignUid The foreign UID to check
     *
     * @return bool
     *
     * @throws Exception
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
        } catch (AspectNotFoundException) {
            return 0;
        }
    }
}
