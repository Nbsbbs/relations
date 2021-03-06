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
     * @var string|null
     */
    protected ?string $domain = null;

    /**
     * @param string $query
     * @param LanguageInterface $language
     * @param bool $isNatural
     */
    public function __construct(string $query, LanguageInterface $language, bool $isNatural = false, ?string $domain = null)
    {
        parent::__construct($query, $language);
        $this->isNatural = $isNatural;
        $this->domain = $domain;
    }

    /**
     * @return bool
     */
    public function isNatural(): bool
    {
        return $this->isNatural;
    }

    /**
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }
}
