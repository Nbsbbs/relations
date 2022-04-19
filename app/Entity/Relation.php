<?php

declare(strict_types=1);

namespace App\Entity;

class Relation
{
    /**
     * @var int
     */
    protected int $lesserQueryId;

    /**
     * @var int
     */
    protected int $greaterQueryId;

    /**
     * @var int
     */
    protected int $weight;

    /**
     * @var string
     */
    protected string $domain;

    /**
     * @param int $lesserQueryId
     * @param int $greaterQueryId
     * @param int $weight
     * @param string $domain
     */
    public function __construct(int $lesserQueryId, int $greaterQueryId, int $weight, string $domain)
    {
        $this->lesserQueryId = $lesserQueryId;
        $this->greaterQueryId = $greaterQueryId;
        $this->domain = $domain;
        $this->weight = $weight;
    }

    /**
     * @return int
     */
    public function getLesserQueryId(): int
    {
        return $this->lesserQueryId;
    }

    /**
     * @return int
     */
    public function getGreaterQueryId(): int
    {
        return $this->greaterQueryId;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }
}
