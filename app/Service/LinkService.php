<?php

namespace App\Service;

use App\Entity\StoredQuery;
use Nbsbbs\Common\DomainInterface;

class LinkService
{
    /**
     * @var \PDO
     */
    private \PDO $pdo;

    /**
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
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
        } elseif ($secondQuery->getId() < $firstQuery->getId()) {
            $query1 = $secondQuery;
            $query2 = $firstQuery;
        } else {
            $query1 = $firstQuery;
            $query2 = $secondQuery;
        }

        try {
            $stmt = $this->pdo->prepare('INSERT INTO `links_history` (`domain`, `query_first`, `query_second`, `weight`, `created_at`, `reason`) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $domain->getDomainName(),
                $query1->getId(),
                $query2->getId(),
                $weight,
                DateTimeService::dateTime()->format('Y-m-d H:i:s'),
                $reason,
            ]);

            return true;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot add link: ' . $e->getMessage());
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
        $stmt = $this->pdo->prepare('SELECT IF(qr.`query_first`=?, qr.`query_second`, qr.`query_first`) AS query_id, SUM(qr.weight) as w FROM `query_relations` qr WHERE qr.query_first=? OR qr.query_second=? GROUP BY query_id HAVING w>? ORDER BY w DESC LIMIT ? OFFSET ?');
        $stmt->execute([$query->getId(), $query->getId(), $query->getId(), $weightThreshold, $limit, $offset]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row['query_id'];
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
        $stmt = $this->pdo->prepare('SELECT IF(`qr`.`query_first`=?, `qr`.`query_second`, `qr`.`query_first`) AS query_id, `qr`.weight  FROM `query_relations` `qr` WHERE (`qr`.query_first=? OR `qr`.query_second=?) AND `qr`.`domain`=? AND `qr`.`weight`>=? ORDER BY `qr`.`weight` DESC LIMIT ? OFFSET ?');
        $stmt->execute([
            $query->getId(),
            $query->getId(),
            $query->getId(),
            $domain->getDomainName(),
            $weightThreshold,
            $limit,
            $offset,
        ]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row['query_id'];
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
        $ids = $this->getRelationsIds($query, 10000, 0, $weightThreshold);
        return sizeof($ids);
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
        $stmt = $this->pdo->prepare('SELECT count(*) from `query_relations` where (`query_first`=? or `query_second`=?) AND weight>=? AND `domain`=?');
        $stmt->execute([$query->getId(), $query->getId(), $weightThreshold, $domain->getDomainName()]);
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            return (int) $row[0];
        } else {
            throw new \RuntimeException('DB error');
        }
    }
}
