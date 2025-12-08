<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Exceptions\PatternCompilationException;
use Cline\Relex\Relex;
use Cline\Relex\ValueObjects\Pattern;

describe('Relex', function (): void {
    describe('test method', function (): void {
        test('returns true for matching pattern', function (): void {
            expect(Relex::test('/\d+/', '123'))->toBeTrue();
        });

        test('returns false for non-matching pattern', function (): void {
            expect(Relex::test('/\d+/', 'abc'))->toBeFalse();
        });

        test('works with offset', function (): void {
            expect(Relex::test('/\d+/', 'a1b', 0))->toBeTrue();
            expect(Relex::test('/\d+/', 'a1b', 2))->toBeFalse();
        });
    });

    describe('compile method', function (): void {
        test('creates Pattern from expression', function (): void {
            $pattern = Relex::compile('\d+');

            expect($pattern)->toBeInstanceOf(Pattern::class);
            expect($pattern->expression())->toBe('\d+');
        });

        test('creates Pattern with custom delimiter', function (): void {
            $pattern = Relex::compile('\d+', '#');

            expect($pattern->delimiter())->toBe('#');
        });
    });

    describe('pattern method', function (): void {
        test('creates Pattern from complete string', function (): void {
            $pattern = Relex::pattern('/\d+/im');

            expect($pattern)->toBeInstanceOf(Pattern::class);
            expect($pattern->expression())->toBe('\d+');
        });
    });

    describe('validate method', function (): void {
        test('validates correct pattern', function (): void {
            Relex::validate('/\d+/');
            expect(true)->toBeTrue();
        });

        test('throws for invalid pattern', function (): void {
            Relex::validate('/[invalid/');
        })->throws(PatternCompilationException::class);
    });

    describe('isValid method', function (): void {
        test('returns true for valid pattern', function (): void {
            expect(Relex::isValid('/\d+/'))->toBeTrue();
        });

        test('returns false for invalid pattern', function (): void {
            expect(Relex::isValid('/[invalid/'))->toBeFalse();
        });
    });

    describe('escape method', function (): void {
        test('escapes special characters', function (): void {
            expect(Relex::escape('hello.world'))->toBe('hello\.world');
            expect(Relex::escape('[test]'))->toBe('\[test\]');
            expect(Relex::escape('$100'))->toBe('\$100');
        });

        test('escapes delimiter', function (): void {
            expect(Relex::escape('a/b', '/'))->toBe('a\/b');
            expect(Relex::escape('a#b', '#'))->toBe('a\#b');
        });
    });

    describe('any method', function (): void {
        test('creates pattern matching any string', function (): void {
            $pattern = Relex::any(['foo', 'bar', 'baz']);

            expect(Relex::test($pattern, 'foo'))->toBeTrue();
            expect(Relex::test($pattern, 'bar'))->toBeTrue();
            expect(Relex::test($pattern, 'baz'))->toBeTrue();
            expect(Relex::test($pattern, 'qux'))->toBeFalse();
        });

        test('escapes special characters in strings', function (): void {
            $pattern = Relex::any(['a.b', 'c[d]']);

            expect(Relex::test($pattern, 'a.b'))->toBeTrue();
            expect(Relex::test($pattern, 'aXb'))->toBeFalse();
        });
    });

    describe('count method', function (): void {
        test('counts matches', function (): void {
            expect(Relex::count('/\d+/', 'a1 b2 c3'))->toBe(3);
            expect(Relex::count('/\d+/', 'no numbers'))->toBe(0);
        });
    });

    describe('extract method', function (): void {
        test('extracts all matches as strings', function (): void {
            expect(Relex::extract('/\d+/', 'a1 b2 c3'))->toBe(['1', '2', '3']);
        });

        test('returns empty array for no matches', function (): void {
            expect(Relex::extract('/\d+/', 'abc'))->toBe([]);
        });
    });

    describe('pluck method', function (): void {
        test('extracts named group from all matches', function (): void {
            $result = Relex::pluck('/(?<num>\d+)/', 'a1 b2 c3', 'num');

            expect($result)->toBe(['1', '2', '3']);
        });
    });

    describe('filter method', function (): void {
        test('filters strings matching pattern', function (): void {
            $strings = ['abc', '123', 'def', '456'];
            $result = Relex::filter('/\d+/', $strings);

            expect($result)->toBe(['123', '456']);
        });

        test('returns empty array when nothing matches', function (): void {
            $strings = ['abc', 'def'];
            $result = Relex::filter('/\d+/', $strings);

            expect($result)->toBe([]);
        });
    });

    describe('reject method', function (): void {
        test('rejects strings matching pattern', function (): void {
            $strings = ['abc', '123', 'def', '456'];
            $result = Relex::reject('/\d+/', $strings);

            expect($result)->toBe(['abc', 'def']);
        });
    });

    describe('first method', function (): void {
        test('finds first matching string', function (): void {
            $strings = ['abc', '123', 'def', '456'];
            $result = Relex::first('/\d+/', $strings);

            expect($result)->toBe('123');
        });

        test('returns null when nothing matches', function (): void {
            $strings = ['abc', 'def'];
            $result = Relex::first('/\d+/', $strings);

            expect($result)->toBeNull();
        });
    });
});
