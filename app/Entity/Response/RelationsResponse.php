<?php

namespace App\Entity\Response;

use Nbsbbs\Common\DomainInterface;
use Nbsbbs\Common\Query\QueryInterface;

class RelationsResponse implements ServiceResponseInterface
{
    /**
     * @var array
     */
    private array $queries = [];

    /**
     * @var DomainInterface|null
     */
    private ?DomainInterface $domain = null;

    /**
     * @var int|null
     */
    private ?int $totalSize = null;

    public function __construct(array $queries, ?DomainInterface $domain = null)
    {
        // better type checking
        foreach ($queries as $query) {
            $this->addQuery($query);
        }

        $this->domain = $domain;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return null;
    }

    /**
     * @return int|null
     */
    public function getErrorCode(): ?int
    {
        return null;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function withTotalSize(int $size): self
    {
        $this->totalSize = $size;
        return $this;
    }

    /**
     * @return int|null
     */
    public function totalSize(): ?int
    {
        return $this->totalSize;
    }

    /**
     * @return int
     */
    public function size(): int
    {
        return sizeof($this->queries);
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
     * @return array|QueryInterface[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * @param QueryInterface $query
     */
    private function addQuery(QueryInterface $query): void
    {
        $this->queries[] = $query;
    }
}
