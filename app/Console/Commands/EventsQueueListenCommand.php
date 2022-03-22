<?php

namespace App\Console\Commands;

use App\Entity\LinkCreationEvent;
use App\Jobs\AddLinkCreationEventJob;
use Illuminate\Console\Command;

class EventsQueueListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:eventsQueueListen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(\GearmanWorker $worker)
    {
        $worker->addFunction(env('GEARMAN_EVENTS_QUEUE'), function (\GearmanJob $job) {
            $workload = $job->workload();
            try {
                $event = LinkCreationEvent::create($workload);
                $this->info('Event created');
                dispatch(new AddLinkCreationEventJob($event));
            } catch (\Throwable $e) {
                $this->warn('Cannot create event '.$workload.': '.$e->getMessage());
            }
        });
        $worker->setTimeout(60000);

        $startStamp = time();
        while ($worker->work()) {
            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                echo "error!";
                break;
            }

            $runningTime = time() - $startStamp;
            if ($runningTime > 60) {
                $this->info('I\'m done.');
                return 0;
            }
        }
        return 0;
    }
}
