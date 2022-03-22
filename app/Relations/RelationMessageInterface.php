<?php

namespace App\Relations;

use Nbsbbs\Common\DomainInterface;
use Nbsbbs\Common\Query\QueryInterface;

interface RelationMessageInterface
{
    /**
     * @return QueryInterface
     */
    public function getFirstQuery(): QueryInterface;

    /**
     * @return QueryInterface
     */
    public function getSecondQuery(): QueryInterface;

    /**
     * @return float
     */
    public function getWeight(): float;

    /**
     * @return DomainInterface
     */
    public function getDomain(): DomainInterface;

    /**
     * @return \DateTime
     */
    public function getTimestamp(): ?\DateTime;
}
