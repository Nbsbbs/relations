<?php

namespace App\Jobs;

use App\Entity\LinkCreationEvent;
use App\Service\LinkService;
use App\Service\QueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AddLinkCreationEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private LinkCreationEvent $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LinkCreationEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(LinkService $linkService, QueryService $queryService)
    {
        $first = $queryService->locateOrCreate($this->event->getQueryFirst());
        $second = $queryService->locateOrCreate($this->event->getQuerySecond());
        if ($first->getId() === $second->getId()) {
            Log::warning('Trying to add relation between the same query: ' . $first->getId() . " ('" . $first->getQuery() . "', '" . $second->getQuery() . "') ");
            return;
        }
        $linkService->addLink(
            $first,
            $second,
            $this->event->getDomain(),
            $this->event->getWeight(),
            $this->event->getReason()
        );
    }
}
