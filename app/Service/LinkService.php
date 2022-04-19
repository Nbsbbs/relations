<?php

namespace App\Service;

use App\Entity\Relation;
use App\Entity\StoredQuery;
use ClickHouseDB\Client;
use Nbsbbs\Common\DomainInterface;

class LinkService
{
    /**
     * @var \PDO
     */
    private \PDO $pdo;

    /**
     * @var Client
     */
    private Client $clickhouseClient;

    /**
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo, Client $clickhouseClient)
    {
        $this->pdo = $pdo;
        $this->clickhouseClient = $clickhouseClient;
    }

    /**
     * @param StoredQuery $firstQuery
     * @param StoredQuery $secondQuery
     * @param DomainInterface $domain
     * @param int $weight
     * @param string $reason
     * @return bool
     */
    public function addLink(
        StoredQuery     $firstQuery,
        StoredQuery     $secondQuery,
        DomainInterface $domain,
        int             $weight = 2,
        string          $reason = ''
    ): bool {
        if ($secondQuery->getId() === $firstQuery->getId()) {
            throw new \InvalidArgumentException('Cannot add relation between the same query');
        }
        $createdDate = DateTimeService::dateTime();

        // add to clickhouse two times
        $this->clickhouseClient->insert(
            'links',
            [
                [
                    $firstQuery->getId(),
                    $secondQuery->getId(),
                    $domain->getDomainName(),
                    $weight,
                    $reason,
                    $createdDate->format('Y-m-d H:i:s'),
                ],
                [
                    $secondQuery->getId(),
                    $firstQuery->getId(),
                    $domain->getDomainName(),
                    $weight,
                    $reason,
                    $createdDate->format('Y-m-d H:i:s'),
                ],
            ],
            [
                'firstQueryId',
                'secondQueryId',
                'domain_name',
                'weight',
                'reason',
                'created_date',
            ]
        );
        return true;
    }

    /**
     * @param StoredQuery $firstQuery
     * @param StoredQuery $secondQuery
     * @param DomainInterface $domain
     * @param int $weight
     * @param string $reason
     * @return \Generator
     */
    public function createLinkJson(
        StoredQuery     $firstQuery,
        StoredQuery     $secondQuery,
        DomainInterface $domain,
        int             $weight = 2,
        string          $reason = ''
    ): \Generator {
        if ($secondQuery->getId() === $firstQuery->getId()) {
            throw new \InvalidArgumentException('Cannot add relation between the same query');
        }
        $createdDate = DateTimeService::dateTime();

        yield json_encode([
            'firstQueryId' => $firstQuery->getId(),
            'secondQueryId' => $secondQuery->getId(),
            'domain_name' => $domain->getDomainName(),
            'weight' => $weight,
            'reason' => $reason,
            'created_date' => $createdDate->format('Y-m-d H:i:s'),
        ]);
        yield json_encode([
            'firstQueryId' => $secondQuery->getId(),
            'secondQueryId' => $firstQuery->getId(),
            'domain_name' => $domain->getDomainName(),
            'weight' => $weight,
            'reason' => $reason,
            'created_date' => $createdDate->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param int $queryId
     * @return \Generator|int[]
     */
    public function getAllLinkedIds(int $queryId): \Generator
    {
        $stmt = $this->pdo->prepare('SELECT distinct(query_second) as id from `links_history` where `query_first`=?');
        $stmt->execute([$queryId]);
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            yield (int) $row[0];
        }

        $stmt = $this->pdo->prepare('SELECT distinct(query_first) as id from `links_history` where `query_second`=?');
        $stmt->execute([$queryId]);
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            yield (int) $row[0];
        }
    }

    /**
     * @param int $queryId
     * @return \Generator
     */
    public function prepareRelations(int $queryId): \Generator
    {
        $stmt = $this->pdo->prepare('select query_first, query_second, sum(weight) as w from links_history where query_first=? group by query_first, query_second');
        $stmt->execute([$queryId]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield new Relation($row['query_first'], $row['query_second'], $row['w'], 'all.com');
        }

        $stmt = $this->pdo->prepare('select query_first, query_second, sum(weight) as w from links_history where query_second=? group by query_first, query_second');
        $stmt->execute([$queryId]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield new Relation($row['query_second'], $row['query_first'], $row['w'], 'all.com');
        }
    }

    /**
     * @param int $lesserQueryId
     * @param int $greaterQueryId
     * @return \Generator|Relation[]
     */
    public function getPairRelations(int $lesserQueryId, int $greaterQueryId): \Generator
    {
        $stmt = $this->pdo->prepare('select query_first, query_second, domain, sum(weight) as w from links_history where query_first=? and query_second=? group by query_first, query_second, domain');
        $stmt->execute([$lesserQueryId, $greaterQueryId]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield new Relation($row['query_first'], $row['query_second'], $row['w'], $row['domain']);
        }
    }
}
