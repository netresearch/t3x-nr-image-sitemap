<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Domain\Repository;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Result;
use Netresearch\NrImageSitemap\Domain\Model\ImageFileReference;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * The image file reference repository.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 *
 * @see    https://www.netresearch.de
 */
final class ImageFileReferenceRepository extends Repository
{
    public function __construct(
        protected PersistenceManagerInterface $persistenceManager,
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context,
    ) {
        parent::__construct();
    }

    /**
     * Returns file references for given file types.
     *
     * @param array<int, FileType|int> $fileTypes
     * @param array<int, int>          $pageList
     * @param array<int, string>       $tables
     * @param array<int, int>          $excludedDoktypes
     * @param string                   $additionalWhere  Raw SQL fragment appended to the `sys_file_reference` query
     *                                                   via `andWhere()`; any leading boolean operator
     *                                                   (`AND` / `OR`) is stripped by
     *                                                   {@see QueryHelper::stripLogicalOperatorPrefix()}. Reference table
     *                                                   aliases as defined in {@see self::getAllRecords()}: `r` for
     *                                                   `sys_file_reference`, `f` for `sys_file`, `p` for `pages`
     *                                                   (e.g. `"r.tablenames = 'pages'"`). Pass an empty string to skip.
     *                                                   Caller is responsible for quoting / parameterising any values.
     *
     * @return array<int, ImageFileReference>
     *
     * @throws InvalidQueryException
     * @throws Exception
     */
    public function findAllImages(
        array $fileTypes,
        array $pageList,
        array $tables,
        array $excludedDoktypes,
        string $additionalWhere,
    ): array {
        $statement       = $this->getAllRecords($fileTypes, $pageList, $tables, $excludedDoktypes, $additionalWhere);
        $existingRecords = [];

        // Walk result set row by row, to prevent too much memory usage
        while ($row = $statement->fetchAssociative()) {
            if (!array_key_exists('tablenames', $row)) {
                continue;
            }

            if (!array_key_exists('uid_foreign', $row)) {
                continue;
            }

            // Check if the foreign table record exists
            if ($this->findRecordByForeignUid((string) $row['tablenames'], (int) $row['uid_foreign'])) {
                $existingRecords[] = (int) $row['uid'];
            }
        }

        // Remove duplicates
        $existingRecords = array_unique($existingRecords);

        if ($existingRecords === []) {
            return [];
        }

        $query = $this->createQuery();

        /** @var array<int, ImageFileReference> $images */
        $images = iterator_to_array(
            $query
                ->matching(
                    $query->in('uid', $existingRecords),
                )
                ->execute(),
        );

        return $images;
    }

    /**
     * Returns all file reference records.
     */
    private function getAllRecords(
        array $fileTypes,
        array $pageList,
        array $tables,
        array $excludedDoktypes = [],
        string $additionalWhere = '',
    ): Result {
        $connection = $this->connectionPool->getConnectionForTable('sys_file_reference');

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('r.uid', 'r.uid_foreign', 'r.tablenames')
            ->from('sys_file_reference', 'r')
            ->leftJoin(
                'r',
                'sys_file',
                'f',
                $queryBuilder->expr()->eq('f.uid', $queryBuilder->quoteIdentifier('r.uid_local')),
            )
            ->leftJoin(
                'r',
                'pages',
                'p',
                $queryBuilder->expr()->eq('p.uid', $queryBuilder->quoteIdentifier('r.pid')),
            )
            ->andWhere(
                $queryBuilder->expr()->in(
                    'p.uid',
                    $queryBuilder->createNamedParameter(
                        $pageList,
                        Connection::PARAM_INT_ARRAY,
                    ),
                ),
            )
            ->andWhere(
                $queryBuilder->expr()->isNotNull('f.uid'),
            )
            ->andWhere(
                $queryBuilder->expr()->eq('f.missing', 0),
            )
            ->andWhere(
                $queryBuilder->expr()->in(
                    'f.type',
                    $queryBuilder->createNamedParameter(
                        $fileTypes,
                        Connection::PARAM_INT_ARRAY,
                    ),
                ),
            )
            ->andWhere(
                $queryBuilder->expr()->in(
                    'r.tablenames',
                    $queryBuilder->createNamedParameter(
                        $tables,
                        Connection::PARAM_STR_ARRAY,
                    ),
                ),
            )
            ->andWhere(
                $queryBuilder->expr()->eq('r.t3ver_wsid', 0),
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'r.sys_language_uid',
                    $queryBuilder->createNamedParameter(
                        $this->getLanguageUid(),
                        Connection::PARAM_INT,
                    ),
                ),
            );

        if ($excludedDoktypes !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'p.doktype',
                    $queryBuilder->createNamedParameter(
                        $excludedDoktypes,
                        Connection::PARAM_INT_ARRAY,
                    ),
                ),
            );
        }

        if ($additionalWhere !== '') {
            $queryBuilder->andWhere(
                QueryHelper::stripLogicalOperatorPrefix($additionalWhere),
            );
        }

        return $queryBuilder->executeQuery();
    }

    /**
     * Returns the UID of the record the foreign table related to or FALSE otherwise.
     *
     * @throws Exception
     */
    private function findRecordByForeignUid(string $tableName, int $foreignUid): bool
    {
        $connection    = $this->connectionPool->getConnectionForTable($tableName);
        $schemaManager = $connection->createSchemaManager();

        // Table did not exist => abort
        if (!$schemaManager->tablesExist([$tableName])) {
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
                        Connection::PARAM_INT,
                    ),
                ),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Returns the current language UID.
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
