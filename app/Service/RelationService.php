<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\StoredQuery;
use ClickHouseDB\Client;
use Nbsbbs\Common\DomainInterface;

class RelationService
{
    /**
     * @var Client
     */
    protected Client $clickhouseClient;

    /**
     * @param Client $clickhouseClient
     */
    public function __construct(Client $clickhouseClient)
    {
        $this->clickhouseClient = $clickhouseClient;
    }

    /**
     * @param StoredQuery $query
     * @param int $limit
     * @param int $offset
     * @param int $weightThreshold
     *
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
     *
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
     *
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
     *
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
