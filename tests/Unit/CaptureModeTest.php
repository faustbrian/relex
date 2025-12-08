<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Enums\CaptureMode;

describe('CaptureMode', function (): void {
    describe('values', function (): void {
        test('has all capture mode values', function (): void {
            expect(CaptureMode::All->value)->toBe('all');
            expect(CaptureMode::First->value)->toBe('first');
            expect(CaptureMode::AllButFirst->value)->toBe('all_but_first');
            expect(CaptureMode::Named->value)->toBe('named');
            expect(CaptureMode::None->value)->toBe('none');
        });
    });

    describe('description', function (): void {
        test('returns description for All mode', function (): void {
            expect(CaptureMode::All->description())->toBe('Capture full match and all groups');
        });

        test('returns description for First mode', function (): void {
            expect(CaptureMode::First->description())->toBe('Capture only the first group');
        });

        test('returns description for AllButFirst mode', function (): void {
            expect(CaptureMode::AllButFirst->description())->toBe('Capture all groups except full match');
        });

        test('returns description for Named mode', function (): void {
            expect(CaptureMode::Named->description())->toBe('Capture only named groups');
        });

        test('returns description for None mode', function (): void {
            expect(CaptureMode::None->description())->toBe('No capture, boolean match only');
        });
    });
});
