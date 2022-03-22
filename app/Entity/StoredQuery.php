<?php

namespace App\Entity;

use App\Relations\StoredQueryInterface;
use Nbsbbs\Common\Language\LanguageInterface;
use Nbsbbs\Common\Query\QueryInterface;

class StoredQuery implements StoredQueryInterface
{
    /**
     * @var int
     */
    private int $id;

    /**
     * @var QueryInterface
     */
    private QueryInterface $query;

    /**
     * @param int $id
     * @param QueryInterface $query
     */
    public function __construct(int $id, QueryInterface $query)
    {
        $this->id = $id;
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query->getQuery();
    }

    /**
     * @return LanguageInterface
     */
    public function getLanguage(): LanguageInterface
    {
        return $this->query->getLanguage();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
