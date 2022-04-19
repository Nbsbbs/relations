<?php

use ClickHouseDB\Client;
use Illuminate\Database\Migrations\Migration;

class ClickhouseLinksCreate extends Migration
{
    protected Client $client;

    public function __construct()
    {
        $container = app();
        $this->client = $container->get(Client::class);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->client->write('CREATE TABLE relations.links (firstQueryId Int32, secondQueryId Int32, `domain_name` String, weight Int8, reason String, created_date DateTime) ENGINE = MergeTree() ORDER BY firstQueryId;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->client->write('DROP TABLE `relations`.links');
    }
}
