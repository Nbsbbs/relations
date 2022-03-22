<?php

namespace Nbsbbs\Common\Queue;

class GearmanClientFactory
{
    /**
     * @var string
     */
    private string $hostIp;

    /**
     * @param string $hostIp
     */
    public function __construct(string $hostIp)
    {
        $this->hostIp = $hostIp;
    }

    /**
     * @return \GearmanClient
     */
    public function create(): \GearmanClient
    {
        $client = new \GearmanClient();
        $client->addServer($this->hostIp);

        return $client;
    }
}
