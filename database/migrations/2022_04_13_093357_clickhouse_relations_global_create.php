<?php

use ClickHouseDB\Client;
use Illuminate\Database\Migrations\Migration;

class ClickhouseRelationsGlobalCreate extends Migration
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
        $this->client->write('CREATE MATERIALIZED VIEW relations.relations_global
(
    `firstQueryId` Int32,
    `secondQueryId` Int32,
    `sum_weight` Int64
)
ENGINE = SummingMergeTree
ORDER BY (firstQueryId,
 secondQueryId)
SETTINGS index_granularity = 8192 AS
SELECT
    firstQueryId,
    secondQueryId,
    sum(weight) AS sum_weight
FROM relations.links AS l
GROUP BY
    firstQueryId,
    secondQueryId;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->client->write('DROP VIEW relations.relations_global');
    }
}
