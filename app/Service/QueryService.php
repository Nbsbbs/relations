<?php

namespace App\Service;

use App\Entity\Query;
use App\Entity\StoredQuery;
use App\Relations\StoredQueryInterface;
use Nbsbbs\Common\Language\LanguageFactory;
use Nbsbbs\Common\Query\QueryInterface;

class QueryService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find or create StoredQuery
     *
     * @param QueryInterface $query
     * @return StoredQueryInterface
     */
    public function locateOrCreate(QueryInterface $query): StoredQueryInterface
    {
        if ($storedQuery = $this->find($query)) {
            return $storedQuery;
        } else {
            return $this->store($query);
        }
    }

    /**
     * @param QueryInterface $query
     * @return StoredQueryInterface
     */
    public function store(QueryInterface $query): StoredQueryInterface
    {
        $stmt = $this->pdo->prepare('INSERT INTO `queries` (`language_code`, `query`, `created_at`) VALUES (?, ?, ?, ?)');

        try {
            $stmt->execute([
                $query->getLanguage()->getCode(),
                $query->getQuery(),
                DateTimeService::dateTime()->format('Y-m-d H:i:s'),
            ]);
            $id = $this->pdo->lastInsertId();
            return new StoredQuery($id, $query);
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $existingQuery = $this->find($query);
                if (is_null($existingQuery)) {
                    throw new \RuntimeException('Error storing new query (duplicate but not found) "' . $query->getQuery() . '" [' . $query->getLanguage()
                                                                                                                                           ->getCode() . ']: ' . $e->getMessage());
                } else {
                    return $existingQuery;
                }
            } else {
                throw new \RuntimeException('Error storing new query "' . $query->getQuery() . '" [' . $query->getLanguage()
                                                                                                             ->getCode() . ']: ' . $e->getMessage());
            }
        }
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findByIds(array $ids): array
    {
        $marks = implode(", ", array_fill(0, sizeof($ids), "?"));
        $result = [];
        $stmt = $this->pdo->prepare('SELECT * FROM `queries` WHERE id IN(' . $marks . ') ORDER BY FIELD(id, ' . $marks . ')');
        $stmt->execute(array_merge($ids, $ids));
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = new StoredQuery($row['id'], new Query($row['query'], LanguageFactory::createLanguage($row['language_code'])));
        }
        return $result;
    }

    /**
     * @param QueryInterface $query
     * @return StoredQueryInterface|null
     */
    public function find(QueryInterface $query): ?StoredQueryInterface
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `queries` WHERE `language_code`=? and `query`=?');
        $stmt->execute([$query->getLanguage()->getCode(), $query->getQuery()]);
        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new StoredQuery($row['id'], new Query($row['query'], LanguageFactory::createLanguage($row['language_code'])));
        }
        return null;
    }
}
