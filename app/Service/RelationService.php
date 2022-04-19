<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Relation;
use App\Entity\StoredQuery;
use ClickHouseDB\Client;
use Illuminate\Support\Facades\Log;
use Nbsbbs\Common\DomainInterface;

class RelationService
{
    /**
     * @var \PDO
     */
    private \PDO $pdo;

    /**
     * @var \PDOStatement|null
     */
    protected ?\PDOStatement $saveRelationStmt = null;

    /**
     * @var Client
     */
    protected Client $clickhouseClient;

    /**
     * @param \PDO $pdo
     * @param Client $clickhouseClient
     */
    public function __construct(\PDO $pdo, Client $clickhouseClient)
    {
        $this->pdo = $pdo;
        $this->clickhouseClient = $clickhouseClient;
    }

    /**
     * @param Relation $relation
     */
    public function saveRelation(Relation $relation): void
    {
        $this->prepareStatement();
        try {
            $firstOk = $this->saveRelationStmt->execute([$relation->getLesserQueryId(), $relation->getGreaterQueryId(), $relation->getWeight(), $relation->getDomain()]);
            $secondOk = $this->saveRelationStmt->execute([$relation->getGreaterQueryId(), $relation->getLesserQueryId(), $relation->getWeight(), $relation->getDomain()]);
            if (!$firstOk or !$secondOk) {
                throw new \RuntimeException('cannot execute query');
            }
        } catch (\Throwable $e) {
            Log::error('Relation save: ' . $e->getMessage());
        }
    }

    protected function prepareStatement(): void
    {
        if (is_null($this->saveRelationStmt)) {
            $this->saveRelationStmt = $this->pdo->prepare('INSERT INTO `query_relations` (`query_first`, `query_second`, `weight`, `domain`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `query_first`=VALUES(query_first), query_second=VALUES(query_second), weight=VALUES(weight), domain=VALUES(domain)');
        }
    }

    /**
     * @param StoredQuery $query
     * @param int $limit
     * @param int $offset
     * @param int $weightThreshold
     * @return array
     */
    public function getRelationsIds(StoredQuery $query, int $limit, int $offset, int $weightThreshold): array
    {
        $result = [];
        $stmt = $this->clickhouseClient->select(
            'SELECT secondQueryId, SUM(sum_weight) as sum_weight FROM `relations`.relations_global WHERE firstQueryId=:query_id GROUP BY secondQueryId HAVING sum_weight>=:sum_weight order by sum_weight DESC, secondQueryId limit :limit OFFSET :offset',
            [
                'query_id' => $query->getId(),
                'sum_weight' => $weightThreshold,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        foreach ($stmt->rows() as $row) {
            $result[] = $row['secondQueryId'];
        }

        return $result;
    }

    /**
     * @param StoredQuery $query
     * @param DomainInterface $domain
     * @param int $limit
     * @param int $offset
     * @param int $weightThreshold
     * @return array
     */
    public function getRelationsIdsWithDomain(
        StoredQuery     $query,
        DomainInterface $domain,
        int             $limit,
        int             $offset,
        int             $weightThreshold
    ): array {
        $result = [];
        $stmt = $this->clickhouseClient->select(
            'SELECT secondQueryId, SUM(sum_weight) as sum_weight FROM relations.relations_domain WHERE firstQueryId=:query_id AND domain_name=:domain_name GROUP BY secondQueryId HAVING sum_weight>=:sum_weight order by sum_weight DESC, secondQueryId limit :limit OFFSET :offset',
            [
                'query_id' => $query->getId(),
                'domain_name' => $domain->getDomainName(),
                'sum_weight' => $weightThreshold,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        foreach ($stmt->rows() as $row) {
            $result[] = $row['secondQueryId'];
        }

        return $result;
    }

    /**
     * @param StoredQuery $query
     * @param int $weightThreshold
     * @return int
     */
    public function getTotalRelations(StoredQuery $query, int $weightThreshold = 0): int
    {
        $stmt = $this->clickhouseClient->select(
            'SELECT count(distinct(secondQueryId)) as cnt FROM relations.relations_global WHERE firstQueryId=:query_id AND sum_weight>=:sum_weight',
            [
                'query_id' => $query->getId(),
                'sum_weight' => $weightThreshold,
            ]
        );
        return (int) $stmt->fetchOne('cnt');
    }

    /**
     * @param StoredQuery $query
     * @param DomainInterface $domain
     * @param int $weightThreshold
     * @return int
     */
    public function getTotalRelationsWithDomain(
        StoredQuery     $query,
        DomainInterface $domain,
        int             $weightThreshold = 0
    ): int {
        $stmt = $this->clickhouseClient->select(
            'SELECT count(distinct(secondQueryId)) as cnt FROM relations.relations_domain WHERE firstQueryId=:query_id AND sum_weight>=:sum_weight AND domain_name=:domain_name',
            [
                'query_id' => $query->getId(),
                'sum_weight' => $weightThreshold,
                'domain_name' => $domain->getDomainName(),
            ]
        );
        return (int) $stmt->fetchOne('cnt');
    }
}
