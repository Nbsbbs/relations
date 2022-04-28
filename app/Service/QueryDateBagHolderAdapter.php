<?php

namespace App\Service;

use Nbsbbs\Common\Query\QueryInterface;

class QueryDateBagHolderAdapter
{
    /**
     * @var DateBagHolder
     */
    private DateBagHolder $bagHolder;

    public function __construct()
    {
        $this->bagHolder = new DateBagHolder();
    }

    /**
     * @param QueryInterface $query
     * @return \DateTimeInterface|null
     */
    public function get(QueryInterface $query): ?\DateTimeInterface
    {
        return $this->bagHolder->get($this->makeId($query));
    }

    /**
     * @param QueryInterface $query
     * @param \DateTimeInterface $dateTime
     * @return void
     */
    public function push(QueryInterface $query, \DateTimeInterface $dateTime): void
    {
        $this->bagHolder->push($this->makeId($query), $dateTime);
    }

    /**
     * @param QueryInterface $query
     * @return string
     */
    protected function makeId(QueryInterface $query): string
    {
        return $query->getLanguage()->getCode() . ':' . $query->getQuery();
    }
}
