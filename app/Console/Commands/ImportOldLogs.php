<?php

namespace App\Console\Commands;

use App\Entity\Domain;
use App\Entity\LinkCreationEvent;
use App\Entity\SearchLogQuery;
use App\Service\LinkService;
use App\Service\Processor\ArrayNormalizer;
use App\Service\QueryService;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Nbsbbs\Common\Language\LanguageFactory;

class ImportOldLogs extends Command
{
    /**
     *
     */
    protected const LOG_STRINGS = 1000000;

    /**
     * @var string
     */
    protected string $logSavePath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:importOldLogs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var string
     */
    protected string $filename = 'unknown';

    /**
     * @var LinkService
     */
    protected LinkService $linkService;

    /**
     * @var QueryService
     */
    protected QueryService $queryService;

    /**
     * @var int
     */
    protected int $logNumber = 0;

    /**
     * @var int
     */
    protected int $stringCounter = 0;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LinkService $linkService, QueryService $queryService)
    {
        parent::__construct();
        $this->linkService = $linkService;
        $this->queryService = $queryService;
        $this->logSavePath = env('PATH_LOG_EXPORT', '/tmp');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->initLogFilename($this->logSavePath);
        ini_set('memory_limit', '12G');
        foreach ($this->walkLogs() as $log) {

            $this->info($log);
            $strings = 0;
            $logData = [];
            foreach ($this->walkStrings($log) as $string) {
                $strings++;
                // if ($strings % 10000 == 0) {
                //     echo $strings.': '.memory_get_usage(true).PHP_EOL;
                // }
                if ($data = $this->parseLogString($string)) {
                    $logData[$data['l']][$data['d']][$data['s']][] = [$data['q'], $data['isSearch']];
                } else {
                    echo $string . PHP_EOL;
                }
                if ($strings % 3000000 == 0) {
                    echo $strings . ': ' . memory_get_usage(true) . PHP_EOL;
                    foreach ($this->processLogData($logData) as $event) {
                        $this->insert($event);
                        // dispatch((new AddLinkCreationEventJob($event))->onQueue('import'));
                    }
                    $logData = [];
                }
            }
            $this->info("total: " . $strings);
            unset($logData);
            $this->info('End');
        }
        return 0;
    }

    /**
     * @param LinkCreationEvent $event
     *
     * @return void
     */
    public function insert(LinkCreationEvent $event): void
    {
        $first = $this->queryService->locateOrCreate($event->getQueryFirst());
        $second = $this->queryService->locateOrCreate($event->getQuerySecond());
        if ($first->getId() === $second->getId()) {
            Log::warning('Trying to add relation between the same query: ' . $first->getId() . " ('" . $first->getQuery() . "', '" . $second->getQuery() . "') ");
            return;
        }
        foreach ($this->linkService->createLinkJson($first, $second, $event->getDomain(), $event->getWeight(), $event->getReason()) as $row) {
            $this->pushString($this->logSavePath, $row);
        }
    }

    /**
     * @param string $path
     *
     * @return void
     */
    protected function initLogFilename(string $path): void
    {
        do {
            $this->logNumber++;
        } while (file_exists($this->getLogFilename($path)));
        $this->info('Init log filename: ' . $this->getLogFilename($path));
    }

    /**
     * @param string $path
     * @param string $logString
     *
     * @return void
     */
    public function pushString(string $path, string $logString)
    {
        $this->stringCounter++;
        if ($this->stringCounter >= self::LOG_STRINGS) {
            $this->stringCounter = 0;
            $this->logNumber++;
            $this->info('Change log filename: ' . $this->getLogFilename($path));
        }
        file_put_contents($this->getLogFilename($path), $logString . PHP_EOL, FILE_APPEND);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getLogFilename(string $path): string
    {
        return $path . '/exportLog-' . $this->logNumber . '.json';
    }

    /**
     * @param array $data
     *
     * @return Generator
     */
    protected function processLogData(array $data): Generator
    {
        foreach ($data as $language => $subData) {
            if (!LanguageFactory::isValidCode($language)) {
                continue;
            }
            foreach ($subData as $domain => $subSubData) {
                foreach ($subSubData as $session => $queriesWithWeight) {
                    $rawList = [];
                    foreach ($queriesWithWeight as $item) {
                        $rawList[] = new SearchLogQuery($item[0], LanguageFactory::createLanguage($language), (bool) $item[1], $domain);
                    }
                    $filteredList = (new ArrayNormalizer())->normalizeQueryList($rawList);

                    $size = sizeof($filteredList);
                    if ($size > 2 and $size < 20) {
                        for ($i = 1; $i < $size; $i++) {
                            $current = $filteredList[$i];
                            $baseLinkPower = $current->isNatural() ? 4 : 2;
                            yield new LinkCreationEvent($current, $filteredList[$i - 1], new Domain($current->getDomain()), $baseLinkPower, 'old logs: ' . $this->filename);
                            if ($i > 1) {
                                if ($current->getQuery() !== $filteredList[$i - 2]->getQuery()) {
                                    yield new LinkCreationEvent($current, $filteredList[$i - 2], new Domain($current->getDomain()), intdiv($baseLinkPower, 2), 'old logs: ' . $this->filename);
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->warn('Reset array');
    }

    /**
     * @param string $filename
     *
     * @return Generator
     */
    protected function walkStrings(string $filename): Generator
    {
        if (preg_match('##s', $filename)) {
            $file = gzopen($filename, 'r');
            while (!feof($file)) {
                yield trim(fgets($file, 4096));
            }
        } else {
            yield from file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
    }

    /**
     * @return Generator
     */
    protected function walkLogs(): Generator
    {
        $path = env('LOGS_OLD_PATH', '/tmp');
        exec("find $path -regex \".*/queryLog.*\" -print", $out);
        $this->info("find $path -regex \".*/queryLog.*\" -print");
        foreach ($out as $filename) {
            $this->filename = $filename;
            yield $filename;
        }
    }

    /**
     * @param string $string
     *
     * @return array|false
     */
    protected function parseLogString(string $string)
    {
        $string = trim($string);
        if (preg_match('~(\d+-\d+-\d+ \d+:\d+:\d+)##([\w\d]{32})##([^#]+)##(\w{2})##(\d)##([^#]+)~s', $string, $args)) {
            return ["t" => $args[1], "s" => $args[2], 'l' => $args[4], 'q' => $this->normalizeQuery($args[6]), 'd' => env('DEFAULT_DOMAIN', 'example.com'), 'isSearch' => ($args[5] > 0)];
        } else {
            $list = explode("||", $string);
            if (sizeof($list) != 7) {
                return false;
            }
            $len = mb_strlen($list[6], "UTF-8");
            if ($len > 60) {
                return false;
            }
            if ($len < 2) {
                return false;
            }
            return ["t" => $list[0], "s" => $list[1], 'l' => $list[3], 'q' => $this->normalizeQuery($list[6]), 'd' => $list[4], 'isSearch' => true];
        }
    }

    /**
     * @param array $queries
     *
     * @return array
     */
    protected function normalizeQueryList(array $queries): array
    {
        $processor = new ArrayNormalizer();
        return $processor->normalizeQueryList($queries);
    }

    /**
     * @param string $query
     *
     * @return string
     */
    protected function normalizeQuery(string $query): string
    {
        $query = mb_strtolower($query, 'UTF-8');
        $query = preg_replace("#\(.+?\)#s", "", $query);
        return $query;
    }
}
