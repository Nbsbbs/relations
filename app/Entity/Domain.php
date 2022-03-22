<?php

namespace App\Entity;

use Nbsbbs\Common\DomainInterface;

class Domain implements DomainInterface
{
    /**
     * @var string
     */
    private string $domainName;

    /**
     * @param string $domainName
     */
    public function __construct(string $domainName)
    {
        $this->domainName = $this->normalize($domainName);
    }

    /**
     * @return string
     */
    public function getDomainName(): string
    {
        return $this->domainName;
    }

    /**
     * @param string $domainName
     * @return string
     */
    private function normalize(string $domainName): string
    {
        return str_replace('www.', '', strtolower($domainName));
    }
}
