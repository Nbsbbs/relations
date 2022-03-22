<?php

namespace App\Relations;

use Nbsbbs\Common\Query\QueryInterface;

interface StoredQueryInterface extends QueryInterface
{
    /**
     * @return int
     */
    public function getId(): int;
}
