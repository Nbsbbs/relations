<?php

namespace App\Service\Processor;

use App\Entity\SearchLogQuery;

class ArrayNormalizer
{
    /**
     * @param array $queries
     * @return array
     */
    public function normalizeQueryList(array $queries): array
    {
        $prev = null;
        $result = [];
        foreach ($queries as $query) {
            if (!($query instanceof SearchLogQuery)) {
                throw new \InvalidArgumentException('All elements of the list must be of type SearchLogQuery');
            }
            if (!is_null($prev)) {
                if ($query->getQuery() !== $prev->getQuery()) {
                    $result[] = $query;
                    $prev = $query;
                }
            } else {
                $result[] = $query;
                $prev = $query;
            }
        }

        return $result;
    }
}
