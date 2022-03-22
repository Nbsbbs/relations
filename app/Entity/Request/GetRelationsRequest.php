<?php

namespace App\Entity\Request;

use Nbsbbs\Common\DomainInterface;
use Nbsbbs\Common\Query\QueryInterface;

class GetRelationsRequest
{
    /**
     * @var QueryInterface
     */
    private QueryInterface $query;

    /**
     * @var DomainInterface|null
     */
    private ?DomainInterface $domain = null;

    /**
     * @var int
     */
    private int $limit = 50;

    /**
     * @var int
     */
    private int $offset = 0;

    /**
     * @var int
     */
    private int $weightThreshold = 0;

    /**
     * @var bool
     */
    private bool $isNeedMetaTotalFound = false;

    /**
     * @param QueryInterface $query
     */
    public function __construct(QueryInterface $query)
    {
        $this->query = $query;
    }

    /**
     * @return $this
     */
    public function withTotalFound(): self
    {
        $this->isNeedMetaTotalFound = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNeedTotalFound(): bool
    {
        return $this->isNeedMetaTotalFound;
    }

    /**
     * @param DomainInterface $domain
     * @return $this
     */
    public function withDomain(DomainInterface $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function withLimitOffset(int $limit, int $offset): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param int $weight
     * @return $this
     */
    public function withWeightThreshold(int $weight): self
    {
        $this->weightThreshold = $weight;
        return $this;
    }

    /**
     * @return QueryInterface
     */
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    /**
     * @return bool
     */
    public function isDomainFiltered(): bool
    {
        return !is_null($this->domain);
    }

    /**
     * @return DomainInterface|null
     */
    public function getDomain(): ?DomainInterface
    {
        return $this->domain;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getWeightThreshold(): int
    {
        return $this->weightThreshold;
    }
}
