<?php

namespace App\Service\Processor;

use App\Entity\SearchLogQuery;
use Nbsbbs\Common\Language\EnglishLanguage;
use PHPUnit\Framework\TestCase;

class ArrayNormalizerTest extends TestCase
{
    /**
     * @dataProvider normalizeQueryListProvider
     */
    public function testNormalizeQueryList(array $source, array $expected)
    {
        $processor = new ArrayNormalizer();
        self::assertEquals($expected, $processor->normalizeQueryList($source));
    }

    /**
     * @return \Generator
     */
    public function normalizeQueryListProvider(): \Generator
    {
        yield [
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
            ],
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
            ],
        ];
        yield [
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
            ],
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
            ],
        ];
        yield [
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
            ],
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
            ],
        ];
        yield [
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
                new SearchLogQuery('a', new EnglishLanguage(), false),
            ],
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
                new SearchLogQuery('a', new EnglishLanguage(), false),
            ],
        ];
        yield [
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), true),
                new SearchLogQuery('c', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
                new SearchLogQuery('a', new EnglishLanguage(), false),
            ],
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
                new SearchLogQuery('b', new EnglishLanguage(), false),
                new SearchLogQuery('c', new EnglishLanguage(), false),
                new SearchLogQuery('a', new EnglishLanguage(), false),
            ],
        ];
        yield [
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
            ],
            [
                new SearchLogQuery('a', new EnglishLanguage(), false),
            ],
        ];
        yield [
            [],
            [],
        ];
    }
}
