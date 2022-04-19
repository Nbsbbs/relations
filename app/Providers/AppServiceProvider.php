<?php

namespace App\Providers;

use App\Service\LinkService;
use App\Service\QueryService;
use App\Service\RelationService;
use App\Service\RequestService;
use ClickHouseDB\Client as ClickhouseClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Nbsbbs\Common\Queue\GearmanClientFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(QueryService::class, function ($app) {
            return new QueryService(DB::connection()->getPdo());
        });
        $this->app->bind(LinkService::class, function ($app) {
            return new LinkService(DB::connection()->getPdo(), $app->get(ClickhouseClient::class));
        });
        $this->app->bind(RelationService::class, function ($app) {
            return new RelationService(DB::connection()->getPdo(), $app->get(ClickhouseClient::class));
        });
        $this->app->bind(RequestService::class, function ($app) {
            return new RequestService(
                $app->make(QueryService::class),
                $app->make(RelationService::class)
            );
        });
        $this->app->singleton(\GearmanClient::class, function ($app) {
            return (new GearmanClientFactory(env('GEARMAN_IP')))->create();
        });
        $this->app->singleton(\GearmanWorker::class, function ($app) {
            $worker = new \GearmanWorker();
            $worker->addServer(env('GEARMAN_IP'));
            return $worker;
        });
        $this->app->singleton(ClickhouseClient::class, function ($app) {
            $client = new ClickhouseClient([
                'host' => env('DB_CLICKHOUSE_HOST', 'localhost'),
                'port' => env('DB_CLICKHOUSE_PORT', 8123),
                'username' => env('DB_CLICKHOUSE_USER', 'default'),
                'password' => env('DB_CLICKHOUSE_PASSWORD', ''),
            ]);
            $client->database(env('DB_CLICKHOUSE_DATABASE', 'default'));
            $client->enableLogQueries(false);
            $client->setTimeout(env('DB_CLICKHOUSE_TIMEOUT', 10));
            $client->useSession();

            return $client;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
