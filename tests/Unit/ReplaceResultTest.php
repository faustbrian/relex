<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Exceptions\ReplaceException;
use Cline\Relex\Relex;
use Cline\Relex\ValueObjects\MatchResult;
use Cline\Relex\ValueObjects\Pattern;

describe('ReplaceResult', function (): void {
    describe('basic replacement', function (): void {
        test('replaces pattern with string', function (): void {
            $result = Relex::replace('/\d+/', 'X', 'a1 b2 c3');

            expect($result->result())->toBe('aX bX cX');
            expect($result->count())->toBe(3);
        });

        test('replaces nothing when no match', function (): void {
            $result = Relex::replace('/\d+/', 'X', 'no numbers');

            expect($result->result())->toBe('no numbers');
            expect($result->count())->toBe(0);
            expect($result->unchanged())->toBeTrue();
        });

        test('replaces with backreferences', function (): void {
            $result = Relex::replace('/(\d+)/', '[$1]', 'a1 b2');

            expect($result->result())->toBe('a[1] b[2]');
        });

        test('replaces with named backreferences', function (): void {
            $result = Relex::replace('/(?<num>\d+)/', '[$1]', 'a1 b2');

            expect($result->result())->toBe('a[1] b[2]');
        });
    });

    describe('callback replacement', function (): void {
        test('replaces with callback', function (): void {
            $result = Relex::replace(
                '/\d+/',
                fn (MatchResult $m): string => (string) ((int) $m->result() * 2),
                'a1 b2 c3',
            );

            expect($result->result())->toBe('a2 b4 c6');
        });

        test('callback receives Match object with groups', function (): void {
            $result = Relex::replace(
                '/(\w)(\d)/',
                fn (MatchResult $m): string => $m->group(1).'-'.$m->group(2),
                'a1 b2',
            );

            expect($result->result())->toBe('a-1 b-2');
        });

        test('callback can access named groups', function (): void {
            $result = Relex::replace(
                '/(?<letter>\w)(?<number>\d)/',
                fn (MatchResult $m): string => $m->group('letter').':'.$m->group('number'),
                'a1 b2',
            );

            expect($result->result())->toBe('a:1 b:2');
        });
    });

    describe('limited replacement', function (): void {
        test('limits number of replacements', function (): void {
            $result = Relex::replace('/\d+/', 'X', 'a1 b2 c3', limit: 2);

            expect($result->result())->toBe('aX bX c3');
            expect($result->count())->toBe(2);
        });

        test('replaces first occurrence only', function (): void {
            $result = Relex::replaceFirst('/\d+/', 'X', 'a1 b2 c3');

            expect($result->result())->toBe('aX b2 c3');
            expect($result->count())->toBe(1);
        });
    });

    describe('array patterns', function (): void {
        test('replaces multiple patterns', function (): void {
            $result = Relex::replace(
                ['/a/', '/b/'],
                ['A', 'B'],
                'a b c',
            );

            expect($result->result())->toBe('A B c');
        });

        test('replaces multiple patterns with single replacement', function (): void {
            $result = Relex::replace(
                ['/a/', '/b/', '/c/'],
                'X',
                'a b c',
            );

            expect($result->result())->toBe('X X X');
        });
    });

    describe('array subjects', function (): void {
        test('replaces in array of subjects', function (): void {
            $result = Relex::replace('/\d+/', 'X', ['a1', 'b2', 'c3']);

            expect($result->result())->toBe(['aX', 'bX', 'cX']);
        });
    });

    describe('chaining', function (): void {
        test('chains replacements', function (): void {
            $result = Relex::replace('/a/', 'A', 'abc')
                ->then('/b/', 'B')
                ->then('/c/', 'C');

            expect($result->result())->toBe('ABC');
        });
    });

    describe('result inspection', function (): void {
        test('checks if replacements were made', function (): void {
            $with = Relex::replace('/\d+/', 'X', 'a1');
            $without = Relex::replace('/\d+/', 'X', 'abc');

            expect($with->hasReplacements())->toBeTrue();
            expect($without->hasReplacements())->toBeFalse();
        });

        test('checks equality', function (): void {
            $result = Relex::replace('/\d+/', 'X', 'a1 b2');

            expect($result->equals('aX bX'))->toBeTrue();
            expect($result->equals('wrong'))->toBeFalse();
        });

        test('converts to string', function (): void {
            $result = Relex::replace('/\d+/', 'X', 'a1');

            expect($result->toString())->toBe('aX');
        });
    });

    describe('Pattern value object', function (): void {
        test('replaces using Pattern object', function (): void {
            $pattern = Pattern::create('\d+');
            $result = Relex::replace($pattern, 'X', 'a1 b2');

            expect($result->result())->toBe('aX bX');
        });
    });

    describe('metadata', function (): void {
        test('returns original pattern', function (): void {
            $result = Relex::replace('/\d+/', 'X', 'a1');

            expect($result->pattern())->toBe('/\d+/');
        });

        test('returns original subject', function (): void {
            $result = Relex::replace('/\d+/', 'X', 'a1');

            expect($result->subject())->toBe('a1');
        });

        test('returns replacement', function (): void {
            $result = Relex::replace('/\d+/', 'X', 'a1');

            expect($result->replacement())->toBe('X');
        });
    });

    describe('error handling', function (): void {
        test('throws exception for invalid pattern', function (): void {
            Relex::replace('/[invalid/', 'X', 'test');
        })->throws(ReplaceException::class);
    });
});
