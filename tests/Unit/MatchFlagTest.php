<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Enums\MatchFlag;

describe('MatchFlag', function (): void {
    describe('values', function (): void {
        test('has correct constant values', function (): void {
            expect(MatchFlag::OffsetCapture->value)->toBe(\PREG_OFFSET_CAPTURE);
            expect(MatchFlag::UnmatchedAsNull->value)->toBe(\PREG_UNMATCHED_AS_NULL);
            expect(MatchFlag::PatternOrder->value)->toBe(\PREG_PATTERN_ORDER);
            expect(MatchFlag::SetOrder->value)->toBe(\PREG_SET_ORDER);
        });
    });

    describe('combine', function (): void {
        test('combines multiple flags', function (): void {
            $combined = MatchFlag::combine([
                MatchFlag::OffsetCapture,
                MatchFlag::UnmatchedAsNull,
            ]);

            expect($combined)->toBe(\PREG_OFFSET_CAPTURE | \PREG_UNMATCHED_AS_NULL);
        });

        test('returns zero for empty array', function (): void {
            expect(MatchFlag::combine([]))->toBe(0);
        });

        test('returns single flag value for one item', function (): void {
            $combined = MatchFlag::combine([MatchFlag::SetOrder]);

            expect($combined)->toBe(\PREG_SET_ORDER);
        });
    });

    describe('description', function (): void {
        test('returns description for OffsetCapture', function (): void {
            expect(MatchFlag::OffsetCapture->description())->toBe('Include byte offsets with matches');
        });

        test('returns description for UnmatchedAsNull', function (): void {
            expect(MatchFlag::UnmatchedAsNull->description())->toBe('Unmatched groups return null');
        });

        test('returns description for PatternOrder', function (): void {
            expect(MatchFlag::PatternOrder->description())->toBe('Group results by capture group');
        });

        test('returns description for SetOrder', function (): void {
            expect(MatchFlag::SetOrder->description())->toBe('Group results by match occurrence');
        });
    });
});
