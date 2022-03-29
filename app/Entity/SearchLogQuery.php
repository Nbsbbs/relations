<?php

namespace App\Entity;

use Nbsbbs\Common\Language\LanguageInterface;

class SearchLogQuery extends Query
{
    /**
     * @var bool
     */
    protected bool $isNatural = false;

    /**
     * @param string $query
     * @param LanguageInterface $language
     * @param bool $isNatural
     */
    public function __construct(string $query, LanguageInterface $language, bool $isNatural = false)
    {
        parent::__construct($query, $language);
        $this->isNatural = $isNatural;
    }

    /**
     * @return bool
     */
    public function isNatural(): bool
    {
        return $this->isNatural;
    }
}
