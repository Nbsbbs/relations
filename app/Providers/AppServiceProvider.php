<?php

namespace App\Providers;

use App\Service\LinkService;
use App\Service\QueryService;
use App\Service\RequestService;
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
            return new LinkService(DB::connection()->getPdo());
        });
        $this->app->bind(RequestService::class, function ($app) {
            return new RequestService(
                $app->make(LinkService::class),
                $app->make(QueryService::class)
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
