<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Support\Position;

describe('Position', function (): void {
    describe('basic properties', function (): void {
        test('stores start and length', function (): void {
            $position = new Position(10, 5);

            expect($position->start)->toBe(10);
            expect($position->length)->toBe(5);
        });

        test('calculates end position', function (): void {
            $position = new Position(10, 5);

            expect($position->end())->toBe(15);
        });

        test('provides accessor methods', function (): void {
            $position = new Position(10, 5);

            expect($position->start())->toBe(10);
            expect($position->length())->toBe(5);
        });
    });

    describe('validity', function (): void {
        test('valid position has non-negative start', function (): void {
            $valid = new Position(0, 5);
            $invalid = new Position(-1, 0);

            expect($valid->isValid())->toBeTrue();
            expect($invalid->isValid())->toBeFalse();
        });
    });

    describe('containment', function (): void {
        test('detects when position contains another', function (): void {
            $outer = new Position(0, 10);
            $inner = new Position(2, 5);
            $outside = new Position(15, 5);

            expect($outer->contains($inner))->toBeTrue();
            expect($outer->contains($outside))->toBeFalse();
            expect($inner->contains($outer))->toBeFalse();
        });

        test('position contains itself', function (): void {
            $position = new Position(5, 10);

            expect($position->contains($position))->toBeTrue();
        });
    });

    describe('overlap', function (): void {
        test('detects overlapping positions', function (): void {
            $a = new Position(0, 10);
            $b = new Position(5, 10);
            $c = new Position(20, 5);

            expect($a->overlaps($b))->toBeTrue();
            expect($b->overlaps($a))->toBeTrue();
            expect($a->overlaps($c))->toBeFalse();
        });

        test('adjacent positions do not overlap', function (): void {
            $a = new Position(0, 10);
            $b = new Position(10, 5);

            expect($a->overlaps($b))->toBeFalse();
        });
    });

    describe('extraction', function (): void {
        test('extracts substring at position', function (): void {
            $position = new Position(4, 3);
            $subject = 'abc 123 def';

            expect($position->extract($subject))->toBe('123');
        });
    });

    describe('factory', function (): void {
        test('creates from offset capture array', function (): void {
            $capture = ['hello', 5];
            $position = Position::fromOffsetCapture($capture);

            expect($position->start())->toBe(5);
            expect($position->length())->toBe(5);
        });
    });
});
