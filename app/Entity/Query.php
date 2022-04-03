<?php

namespace App\Entity;

use Nbsbbs\Common\Language\LanguageInterface;
use Nbsbbs\Common\Query\QueryInterface;

class Query implements QueryInterface
{
    /**
     * @var string
     */
    protected string $query;

    /**
     * @var LanguageInterface
     */
    protected LanguageInterface $language;

    /**
     * @param string $query
     * @param LanguageInterface $language
     */
    public function __construct(string $query, LanguageInterface $language)
    {
        $this->query = self::normalizeQueryText($query);
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return LanguageInterface
     */
    public function getLanguage(): LanguageInterface
    {
        return $this->language;
    }

    /**
     * @param string $queryText
     * @return string
     */
    public static function normalizeQueryText(string $queryText): string
    {
        $low = mb_strtolower($queryText);
        $spaces = preg_replace("#\s+#", " ", $low);
        return trim($low);
    }
}
