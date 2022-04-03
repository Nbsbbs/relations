<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinksHistoryCreate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('links_history', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->nullable(false);
            $table->bigInteger('query_first', false, true)->index();
            $table->bigInteger('query_second', false, true)->index();
            $table->tinyInteger('weight')->default(2);
            $table->dateTime('created_at')->useCurrent()->index();
            $table->string('reason', 255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('links_history');
    }
}
