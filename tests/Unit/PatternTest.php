<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Enums\Modifier;
use Cline\Relex\Exceptions\PatternCompilationException;
use Cline\Relex\ValueObjects\Pattern;

describe('Pattern', function (): void {
    describe('creation', function (): void {
        test('creates pattern from expression', function (): void {
            $pattern = Pattern::create('\d+');

            expect($pattern->expression())->toBe('\d+');
            expect($pattern->delimiter())->toBe('/');
            expect($pattern->toString())->toBe('/\d+/');
        });

        test('creates pattern with custom delimiter', function (): void {
            $pattern = Pattern::create('\d+', '#');

            expect($pattern->delimiter())->toBe('#');
            expect($pattern->toString())->toBe('#\d+#');
        });

        test('creates pattern from complete string', function (): void {
            $pattern = Pattern::from('/\d+/im');

            expect($pattern->expression())->toBe('\d+');
            expect($pattern->delimiter())->toBe('/');
            expect($pattern->hasModifier(Modifier::CaseInsensitive))->toBeTrue();
            expect($pattern->hasModifier(Modifier::Multiline))->toBeTrue();
        });

        test('creates pattern from string with different delimiter', function (): void {
            $pattern = Pattern::from('#\d+#u');

            expect($pattern->expression())->toBe('\d+');
            expect($pattern->delimiter())->toBe('#');
            expect($pattern->hasModifier(Modifier::Utf8))->toBeTrue();
        });

        test('throws exception for missing delimiter', function (): void {
            Pattern::from('/no-closing-delimiter');
        })->throws(PatternCompilationException::class);

        test('throws exception for empty pattern', function (): void {
            Pattern::from('');
        })->throws(PatternCompilationException::class);
    });

    describe('modifiers', function (): void {
        test('adds modifier using verbose method', function (): void {
            $pattern = Pattern::create('\d+')->caseInsensitive();

            expect($pattern->hasModifier(Modifier::CaseInsensitive))->toBeTrue();
            expect($pattern->toString())->toBe('/\d+/i');
        });

        test('adds modifier using shorthand method', function (): void {
            $pattern = Pattern::create('\d+')->i();

            expect($pattern->hasModifier(Modifier::CaseInsensitive))->toBeTrue();
        });

        test('chains multiple modifiers', function (): void {
            $pattern = Pattern::create('\d+')
                ->caseInsensitive()
                ->multiline()
                ->utf8();

            expect($pattern->modifierString())->toBe('imu');
            expect($pattern->toString())->toBe('/\d+/imu');
        });

        test('chains shorthand modifiers', function (): void {
            $pattern = Pattern::create('\d+')->i()->m()->s()->u();

            expect($pattern->modifierString())->toBe('imsu');
        });

        test('adds modifiers using withModifiers', function (): void {
            $pattern = Pattern::create('\d+')->withModifiers(
                Modifier::CaseInsensitive,
                Modifier::Multiline,
                Modifier::Utf8,
            );

            expect($pattern->modifiers())->toHaveCount(3);
        });

        test('removes modifier', function (): void {
            $pattern = Pattern::create('\d+')
                ->caseInsensitive()
                ->multiline()
                ->withoutModifier(Modifier::CaseInsensitive);

            expect($pattern->hasModifier(Modifier::CaseInsensitive))->toBeFalse();
            expect($pattern->hasModifier(Modifier::Multiline))->toBeTrue();
        });

        test('does not duplicate modifiers', function (): void {
            $pattern = Pattern::create('\d+')
                ->caseInsensitive()
                ->caseInsensitive();

            expect($pattern->modifiers())->toHaveCount(1);
        });

        test('supports all modifier types', function (): void {
            $pattern = Pattern::create('test')
                ->caseInsensitive()  // i
                ->multiline()        // m
                ->singleLine()       // s
                ->extended()         // x
                ->utf8()             // u
                ->anchored()         // A
                ->dollarEndOnly()    // D
                ->ungreedy()         // U
                ->duplicateNames()   // J
                ->study();           // S

            expect($pattern->modifiers())->toHaveCount(10);
        });
    });

    describe('validation', function (): void {
        test('validates correct pattern', function (): void {
            Pattern::validate('/\d+/');
            expect(true)->toBeTrue(); // No exception thrown
        });

        test('throws exception for invalid pattern', function (): void {
            Pattern::validate('/[invalid/');
        })->throws(PatternCompilationException::class);

        test('checks validity without throwing', function (): void {
            expect(Pattern::isValid('/\d+/'))->toBeTrue();
            expect(Pattern::isValid('/[invalid/'))->toBeFalse();
        });
    });

    describe('introspection', function (): void {
        test('gets named group names', function (): void {
            $pattern = Pattern::create('(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})');

            expect($pattern->groupNames())->toBe(['year', 'month', 'day']);
        });

        test('returns empty array for pattern without named groups', function (): void {
            $pattern = Pattern::create('(\d+)-(\d+)');

            expect($pattern->groupNames())->toBe([]);
        });

        test('detects named groups', function (): void {
            $withNamed = Pattern::create('(?<name>\w+)');
            $withoutNamed = Pattern::create('(\w+)');

            expect($withNamed->hasNamedGroups())->toBeTrue();
            expect($withoutNamed->hasNamedGroups())->toBeFalse();
        });

        test('supports alternate named group syntax', function (): void {
            $pattern = Pattern::create("(?'year'\\d{4})-(?P<month>\\d{2})");

            expect($pattern->groupNames())->toContain('year');
            expect($pattern->groupNames())->toContain('month');
        });
    });

    describe('string conversion', function (): void {
        test('converts to string implicitly', function (): void {
            $pattern = Pattern::create('\d+')->i()->m();

            expect((string) $pattern)->toBe('/\d+/im');
        });

        test('converts to string explicitly', function (): void {
            $pattern = Pattern::create('\d+')->utf8();

            expect($pattern->toString())->toBe('/\d+/u');
        });
    });

    describe('immutability', function (): void {
        test('modifier methods return new instances', function (): void {
            $original = Pattern::create('\d+');
            $modified = $original->caseInsensitive();

            expect($original)->not->toBe($modified);
            expect($original->hasModifier(Modifier::CaseInsensitive))->toBeFalse();
            expect($modified->hasModifier(Modifier::CaseInsensitive))->toBeTrue();
        });
    });
});
