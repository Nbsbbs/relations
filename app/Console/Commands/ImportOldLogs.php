<?php

namespace App\Console\Commands;

use App\Entity\Domain;
use App\Entity\LinkCreationEvent;
use App\Entity\Query;
use App\Entity\SearchLogQuery;
use App\Service\DateBagHolder;
use App\Service\LinkService;
use App\Service\Processor\ArrayNormalizer;
use App\Service\QueryService;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Nbsbbs\Common\Language\LanguageFactory;
use Nbsbbs\Common\Query\QueryInterface;
use Throwable;

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
     * @var DateBagHolder
     */
    protected DateBagHolder $bagHolder;

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
        $this->bagHolder = new DateBagHolder();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->initLogFilename($this->logSavePath);
        ini_set('memory_limit', '12G');
        foreach ($this->walkLogs() as $log) {
            $this->info($log);
            $strings = 0;
            $logData = [];
            $this->bagHolder->reset();

            foreach ($this->walkStrings($log) as $string) {
                $strings++;
                if ($data = $this->parseLogString($string)) {
                    $query = new Query($data['q'], LanguageFactory::createLanguage($data['l']));
                    try {
                        $this->bagHolder->push($this->createBagHolderId($query), new \DateTimeImmutable($data['t']));
                    } catch (\Exception $e) {
                        $this->warn($e->getMessage());
                    }
                    $logData[$data['l']][$data['d']][$data['s']][] = [$data['q'], $data['isSearch']];
                } else {
                    file_put_contents('ignoredStrings.txt', $string . PHP_EOL, FILE_APPEND);
                }
                if ($strings % 3000000 == 0) {
                    echo $strings . ': ' . memory_get_usage(true) . PHP_EOL;
                    $this->processEventArray($logData);
                    $logData = [];
                    $this->bagHolder->reset();
                }
            }
            $this->info("total: " . $strings);
            if (!empty($logData)) {
                $this->processEventArray($logData);
            }
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
        $first = $this->queryService->locateOrCreate($event->getQueryFirst(), $this->bagHolder->get($this->createBagHolderId($event->getQueryFirst())));
        $second = $this->queryService->locateOrCreate($event->getQuerySecond(), $this->bagHolder->get($this->createBagHolderId($event->getQuerySecond())));
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
     * @param LinkCreationEvent $event
     *
     * @return void
     */
    protected function validateEvent(LinkCreationEvent $event)
    {
        if (mb_strlen($event->getQueryFirst()->getQuery(), 'UTF-8') > 128) {
            throw new InvalidArgumentException('Query too long: ' . $event->getQueryFirst()->getQuery());
        }
        if (mb_strlen($event->getQuerySecond()->getQuery(), 'UTF-8') > 128) {
            throw new InvalidArgumentException('Query too long: ' . $event->getQuerySecond()->getQuery());
        }
        if (preg_match('#^\d+$#', $event->getQueryFirst()->getQuery())) {
            throw new InvalidArgumentException('Query is numeric: ' . $event->getQueryFirst()->getQuery());
        }
        if (preg_match('#^\d+$#', $event->getQuerySecond()->getQuery())) {
            throw new InvalidArgumentException('Query is numeric: ' . $event->getQuerySecond()->getQuery());
        }
    }

    /**
     * @param array $logData
     *
     * @return void
     */
    protected function processEventArray(array $logData): void
    {
        $this->info('Processing event array');
        foreach ($this->processLogData($logData) as $event) {
            try {
                $this->validateEvent($event);
                $this->insert($event);
            } catch (Throwable $e) {
                file_put_contents('warnings.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
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
                foreach ($subSubData as $queriesWithWeight) {
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
        foreach ($this->sortLogsFilenamesByDate($this->logFilenames()) as $monthlyLogs) {
            foreach ($monthlyLogs as $logs) {
                foreach ($logs as $log) {
                    $this->filename = $log;
                    yield $log;
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function logFilenames(): array
    {
        $result = [];
        $path = env('LOGS_OLD_PATH', '/tmp');
        exec("find $path -regex \".*/query.og.*\\.txt\" -print", $out);
        $this->info("find $path -regex  \".*/query.og.*\\.txt\" -print");
        foreach ($out as $filename) {
            $result[] = $filename;
        }
        return $result;
    }

    /**
     * @param array $logs
     * @return array
     */
    protected function sortLogsFilenamesByDate(array $logs): array
    {
        $result = [];
        foreach ($logs as $log) {
            if (preg_match('#_(\d{4})_(\d{2})\.#', $log, $args)) {
                $year = $args[1];
                $month = ltrim($args[2], '0');
            } elseif (preg_match('#queryLog(\d{4})(\d{2})\.#', $log, $args)) {
                $year = $args[1];
                $month = ltrim($args[2], '0');
            } else {
                throw new \RuntimeException('Bad log filename:' . $log);
            }
            if (!isset($result[$year][$month])) {
                $result[$year][$month] = [];
            }
            $result[$year][$month][] = $log;
        }

        $sortedResult = [];
        ksort($result, SORT_NUMERIC);
        foreach ($result as $year => $logs) {
            $sortedResult[$year] = $logs;
            ksort($sortedResult[$year], SORT_NUMERIC);
        }

        return $sortedResult;
    }

    /**
     * @param string $string
     *
     * @return array|false
     */
    protected function parseLogString(string $string)
    {
        $string = trim($string);
        if (preg_match('~(\d+-\d+-\d+ \d+:\d+:\d+)##([\w\d]{32})##([^#]+)##(\w{2})##(\d)##([^#]+)~', $string, $args)) {
            return ["t" => $args[1], "s" => $args[2], 'l' => $args[4], 'q' => $this->normalizeQuery($args[6]), 'd' => env('DEFAULT_DOMAIN', 'example.com'), 'isSearch' => ($args[5] > 0)];
        } elseif (preg_match('~(\d+-\d+-\d+ \d+:\d+:\d+)\|\|([\w\d]{32})\|\|(\w+)\|\|(\w{2})\|\|([^|]+)\|\|([\d.]+)\|\|([^|]+)~', $string, $args)) {
            return ["t" => $args[1], "s" => $args[2], 'l' => $args[4], 'q' => $this->normalizeQuery($args[7]), 'd' => $args[5], 'isSearch' => 0];
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
     * @param string $query
     *
     * @return string
     */
    protected function normalizeQuery(string $query): string
    {
        $query = mb_strtolower($query, 'UTF-8');
        return preg_replace("#\(.+?\)#s", "", $query);
    }

    /**
     * @param QueryInterface $query
     *
     * @return string
     */
    private function createBagHolderId(QueryInterface $query): string
    {
        return $query->getLanguage()->getCode() . ':' . $query->getQuery();
    }
}
