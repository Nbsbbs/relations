<?php

namespace App\Entity;

use Nbsbbs\Common\DomainInterface;
use Nbsbbs\Common\Language\LanguageFactory;
use Nbsbbs\Common\Query\QueryInterface;

class LinkCreationEvent
{
    /**
     * @var QueryInterface
     */
    private QueryInterface $queryFirst;

    /**
     * @var QueryInterface
     */
    private QueryInterface $querySecond;

    /**
     * @var int
     */
    private int $weight;

    /**
     * @var DomainInterface
     */
    private DomainInterface $domain;

    /**
     * @var string
     */
    private string $reason;

    /**
     * @param QueryInterface $queryFirst
     * @param QueryInterface $querySecond
     * @param DomainInterface $domain
     * @param int $weight
     * @param string $reason
     */
    public function __construct(
        QueryInterface  $queryFirst,
        QueryInterface  $querySecond,
        DomainInterface $domain,
        int             $weight = 2,
        string          $reason = ''
    ) {
        $this->queryFirst = $queryFirst;
        $this->querySecond = $querySecond;
        $this->weight = $weight;
        $this->domain = $domain;
        $this->reason = $reason;
    }

    /**
     * @param string $data
     * @return static
     */
    public static function create(string $data): self
    {
        $decodedData = \json_decode($data, true);
        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Data cannot be decoded: must be JSON-encoded');
        }
        if (!is_array($decodedData)) {
            throw new \InvalidArgumentException('Data cannot be decoded: must be array');
        }
        if (empty($decodedData['lang_code'])) {
            throw new \InvalidArgumentException('Insufficient data: must have lang_code');
        } elseif (!LanguageFactory::isValidCode($decodedData['lang_code'])) {
            throw new \InvalidArgumentException('Bad data: invalid lang_code');
        }
        if (empty($decodedData['queries'])) {
            throw new \InvalidArgumentException('Insufficient data: must have queries');
        } elseif (!is_array($decodedData['queries']) or sizeof($decodedData['queries']) !== 2) {
            throw new \InvalidArgumentException('Insufficient data: must contain exactly 2 queries');
        }
        if (empty($decodedData['domain'])) {
            throw new \InvalidArgumentException('Insufficient data: must have domain');
        }
        if (empty($decodedData['weight'])) {
            throw new \InvalidArgumentException('Insufficient data: must have weight');
        } elseif (!is_int($decodedData['weight'])) {
            throw new \InvalidArgumentException('Bad data: must have integer weight');
        } elseif ($decodedData['weight'] === 0) {
            throw new \InvalidArgumentException('Bad data: weight must be over 0');
        }
        return new LinkCreationEvent(
            new Query($decodedData['queries'][0], LanguageFactory::createLanguage($decodedData['lang_code'])),
            new Query($decodedData['queries'][1], LanguageFactory::createLanguage($decodedData['lang_code'])),
            new Domain($decodedData['domain']),
            $decodedData['weight'],
            $decodedData['reason'] ?? ''
        );
    }

    /**
     * @param LinkCreationEvent $event
     * @return string
     */
    public static function encode(self $event): string
    {
        $result = [
            'queries' => [
                $event->getQueryFirst()->getQuery(),
                $event->getQuerySecond()->getQuery(),
            ],
            'lang_code' => $event->getQueryFirst()->getLanguage()->getCode(),
            'domain' => $event->getDomain()->getDomainName(),
            'weight' => $event->getWeight(),
            'reason' => $event->getReason()
        ];
        return json_encode($result);
    }

    /**
     * @return QueryInterface
     */
    public function getQueryFirst(): QueryInterface
    {
        return $this->queryFirst;
    }

    /**
     * @return QueryInterface
     */
    public function getQuerySecond(): QueryInterface
    {
        return $this->querySecond;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @return DomainInterface
     */
    public function getDomain(): DomainInterface
    {
        return $this->domain;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
