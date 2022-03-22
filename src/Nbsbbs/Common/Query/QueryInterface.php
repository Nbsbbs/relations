<?php

namespace Nbsbbs\Common\Query;

use Nbsbbs\Common\Language\LanguageInterface;

interface QueryInterface
{
    /**
     * @return string
     */
    public function getQuery(): string;

    /**
     * @return LanguageInterface
     */
    public function getLanguage(): LanguageInterface;
}
