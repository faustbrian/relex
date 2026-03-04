<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Exceptions\SplitException;
use Cline\Relex\Relex;
use Cline\Relex\ValueObjects\Pattern;

describe('SplitResult', function (): void {
    describe('basic splitting', function (): void {
        test('splits by pattern', function (): void {
            $result = Relex::split('/\s+/', 'a b  c');

            expect($result->results())->toBe(['a', 'b', 'c']);
        });

        test('splits by comma', function (): void {
            $result = Relex::split('/,\s*/', 'a, b,c, d');

            expect($result->results())->toBe(['a', 'b', 'c', 'd']);
        });

        test('returns single element for no match', function (): void {
            $result = Relex::split('/\d+/', 'no numbers');

            expect($result->results())->toBe(['no numbers']);
        });

        test('handles empty segments', function (): void {
            $result = Relex::split('/,/', 'a,,b');

            // PREG_SPLIT_NO_EMPTY removes empty segments
            expect($result->results())->toBe(['a', 'b']);
        });
    });

    describe('limited splitting', function (): void {
        test('limits number of segments', function (): void {
            $result = Relex::split('/\s+/', 'a b c d', limit: 2);

            expect($result->results())->toBe(['a', 'b c d']);
        });

        test('limit of 3', function (): void {
            $result = Relex::split('/\s+/', 'a b c d e', limit: 3);

            expect($result->results())->toBe(['a', 'b', 'c d e']);
        });
    });

    describe('with delimiters', function (): void {
        test('keeps delimiters in output', function (): void {
            $result = Relex::splitWithDelimiters('/(\s+)/', 'a b  c');

            expect($result->results())->toContain(' ');
            expect($result->results())->toContain('  ');
        });

        test('keeps specific delimiter pattern', function (): void {
            $result = Relex::splitWithDelimiters('/(,)/', 'a,b,c');

            expect($result->results())->toBe(['a', ',', 'b', ',', 'c']);
        });
    });

    describe('accessing segments', function (): void {
        test('gets first segment', function (): void {
            $result = Relex::split('/,/', 'a,b,c');

            expect($result->first())->toBe('a');
        });

        test('gets last segment', function (): void {
            $result = Relex::split('/,/', 'a,b,c');

            expect($result->last())->toBe('c');
        });

        test('gets segment by index', function (): void {
            $result = Relex::split('/,/', 'a,b,c');

            expect($result->get(0))->toBe('a');
            expect($result->get(1))->toBe('b');
            expect($result->get(2))->toBe('c');
            expect($result->get(5))->toBeNull();
        });

        test('returns null for first/last when empty', function (): void {
            $result = Relex::split('/,/', '');

            expect($result->first())->toBeNull();
            expect($result->last())->toBeNull();
        });
    });

    describe('iteration', function (): void {
        test('iterates over segments', function (): void {
            $result = Relex::split('/,/', 'a,b,c');
            $segments = [];

            foreach ($result as $segment) {
                $segments[] = $segment;
            }

            expect($segments)->toBe(['a', 'b', 'c']);
        });

        test('uses each callback', function (): void {
            $result = Relex::split('/,/', 'a,b,c');
            $segments = [];

            $result->each(function (string $segment, int $index) use (&$segments): void {
                $segments[$index] = $segment;
            });

            expect($segments)->toBe([0 => 'a', 1 => 'b', 2 => 'c']);
        });
    });

    describe('filtering', function (): void {
        test('filters segments', function (): void {
            $result = Relex::split('/,/', 'a,bb,ccc');
            $filtered = $result->filter(fn (string $s): bool => mb_strlen($s) > 1);

            expect($filtered->results())->toBe(['bb', 'ccc']);
        });
    });

    describe('mapping', function (): void {
        test('maps over segments', function (): void {
            $result = Relex::split('/,/', 'a,b,c');
            $mapped = $result->map(fn (string $s): string => mb_strtoupper($s));

            expect($mapped)->toBe(['A', 'B', 'C']);
        });
    });

    describe('taking and skipping', function (): void {
        test('takes first N segments', function (): void {
            $result = Relex::split('/,/', 'a,b,c,d');

            expect($result->take(2)->results())->toBe(['a', 'b']);
        });

        test('skips first N segments', function (): void {
            $result = Relex::split('/,/', 'a,b,c,d');

            expect($result->skip(2)->results())->toBe(['c', 'd']);
        });
    });

    describe('utilities', function (): void {
        test('joins segments', function (): void {
            $result = Relex::split('/,/', 'a,b,c');

            expect($result->join('-'))->toBe('a-b-c');
            expect($result->join(''))->toBe('abc');
        });

        test('reverses segments', function (): void {
            $result = Relex::split('/,/', 'a,b,c');

            expect($result->reverse()->results())->toBe(['c', 'b', 'a']);
        });

        test('gets unique segments', function (): void {
            $result = Relex::split('/,/', 'a,b,a,c,b');

            expect($result->unique()->results())->toBe(['a', 'b', 'c']);
        });

        test('converts to array', function (): void {
            $result = Relex::split('/,/', 'a,b,c');

            expect($result->toArray())->toBe(['a', 'b', 'c']);
        });
    });

    describe('countable', function (): void {
        test('is countable', function (): void {
            $result = Relex::split('/,/', 'a,b,c');

            expect(count($result))->toBe(3);
            expect($result->count())->toBe(3);
        });

        test('checks empty state', function (): void {
            $empty = Relex::split('/,/', '');
            $notEmpty = Relex::split('/,/', 'a,b');

            expect($empty->isEmpty())->toBeTrue();
            expect($notEmpty->isEmpty())->toBeFalse();
            expect($notEmpty->isNotEmpty())->toBeTrue();
        });
    });

    describe('Pattern value object', function (): void {
        test('splits using Pattern object', function (): void {
            $pattern = Pattern::create('\s+');
            $result = Relex::split($pattern, 'a b c');

            expect($result->results())->toBe(['a', 'b', 'c']);
        });
    });

    describe('metadata', function (): void {
        test('returns original pattern', function (): void {
            $result = Relex::split('/,/', 'a,b');

            expect($result->pattern())->toBe('/,/');
        });

        test('returns original subject', function (): void {
            $result = Relex::split('/,/', 'a,b');

            expect($result->subject())->toBe('a,b');
        });
    });

    describe('error handling', function (): void {
        test('throws exception for invalid pattern', function (): void {
            Relex::split('/[invalid/', 'test');
        })->throws(SplitException::class);
    });
});
