<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Enums\Modifier;

describe('Modifier', function (): void {
    describe('values', function (): void {
        test('has correct string values', function (): void {
            expect(Modifier::CaseInsensitive->value)->toBe('i');
            expect(Modifier::Multiline->value)->toBe('m');
            expect(Modifier::SingleLine->value)->toBe('s');
            expect(Modifier::Extended->value)->toBe('x');
            expect(Modifier::Utf8->value)->toBe('u');
            expect(Modifier::Anchored->value)->toBe('A');
            expect(Modifier::DollarEndOnly->value)->toBe('D');
            expect(Modifier::Ungreedy->value)->toBe('U');
            expect(Modifier::DuplicateNames->value)->toBe('J');
            expect(Modifier::Study->value)->toBe('S');
        });
    });

    describe('char method', function (): void {
        test('returns modifier character', function (): void {
            expect(Modifier::CaseInsensitive->char())->toBe('i');
            expect(Modifier::Utf8->char())->toBe('u');
        });
    });

    describe('description method', function (): void {
        test('returns description for CaseInsensitive', function (): void {
            expect(Modifier::CaseInsensitive->description())->toBe('Case-insensitive matching');
        });

        test('returns description for Multiline', function (): void {
            expect(Modifier::Multiline->description())->toBe('Multiline mode (^ and $ match line boundaries)');
        });

        test('returns description for SingleLine', function (): void {
            expect(Modifier::SingleLine->description())->toBe('Single-line mode (dot matches newlines)');
        });

        test('returns description for Extended', function (): void {
            expect(Modifier::Extended->description())->toBe('Extended mode (whitespace ignored, # comments)');
        });

        test('returns description for Utf8', function (): void {
            expect(Modifier::Utf8->description())->toBe('UTF-8 mode');
        });

        test('returns description for Anchored', function (): void {
            expect(Modifier::Anchored->description())->toBe('Anchored at start of subject');
        });

        test('returns description for DollarEndOnly', function (): void {
            expect(Modifier::DollarEndOnly->description())->toBe('Dollar matches only at end of string');
        });

        test('returns description for Ungreedy', function (): void {
            expect(Modifier::Ungreedy->description())->toBe('Ungreedy quantifiers by default');
        });

        test('returns description for DuplicateNames', function (): void {
            expect(Modifier::DuplicateNames->description())->toBe('Allow duplicate named groups');
        });

        test('returns description for Study', function (): void {
            expect(Modifier::Study->description())->toBe('Study pattern for optimization');
        });
    });

    describe('toString static method', function (): void {
        test('converts array to modifier string', function (): void {
            $modifiers = [Modifier::CaseInsensitive, Modifier::Multiline, Modifier::Utf8];

            expect(Modifier::toString($modifiers))->toBe('imu');
        });

        test('returns empty string for empty array', function (): void {
            expect(Modifier::toString([]))->toBe('');
        });
    });

    describe('fromString static method', function (): void {
        test('parses modifier string to array', function (): void {
            $modifiers = Modifier::fromString('imu');

            expect($modifiers)->toHaveCount(3);
            expect($modifiers)->toContain(Modifier::CaseInsensitive);
            expect($modifiers)->toContain(Modifier::Multiline);
            expect($modifiers)->toContain(Modifier::Utf8);
        });

        test('ignores invalid modifier characters', function (): void {
            $modifiers = Modifier::fromString('ixz');

            expect($modifiers)->toHaveCount(2);
            expect($modifiers)->toContain(Modifier::CaseInsensitive);
            expect($modifiers)->toContain(Modifier::Extended);
        });

        test('returns empty array for empty string', function (): void {
            expect(Modifier::fromString(''))->toBe([]);
        });
    });
});
