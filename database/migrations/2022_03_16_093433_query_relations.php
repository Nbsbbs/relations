<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QueryRelations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('query_relations', function (Blueprint $table) {
            $table->bigInteger('query_first');
            $table->bigInteger('query_second');
            $table->integer('weight');
            $table->string('domain', 255);
            $table->unique(['query_first', 'query_second', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('query_relations');
    }
}
