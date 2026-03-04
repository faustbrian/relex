<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Enums\CaptureMode;
use Cline\Relex\Relex;
use Cline\Relex\ValueObjects\MatchResult;
use Cline\Relex\ValueObjects\Pattern;

describe('MatchAllResult', function (): void {
    describe('basic matching', function (): void {
        test('finds all matches', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3');

            expect($matches->hasMatch())->toBeTrue();
            expect($matches->count())->toBe(3);
            expect($matches->results())->toBe(['1', '2', '3']);
        });

        test('returns empty for no matches', function (): void {
            $matches = Relex::matchAll('/\d+/', 'no numbers');

            expect($matches->hasMatch())->toBeFalse();
            expect($matches->isEmpty())->toBeTrue();
            expect($matches->count())->toBe(0);
        });
    });

    describe('accessing matches', function (): void {
        test('gets first match', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3');
            $first = $matches->first();

            expect($first)->toBeInstanceOf(MatchResult::class);
            expect($first->result())->toBe('1');
        });

        test('gets last match', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3');
            $last = $matches->last();

            expect($last)->toBeInstanceOf(MatchResult::class);
            expect($last->result())->toBe('3');
        });

        test('gets match by index', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3');

            expect($matches->get(0)->result())->toBe('1');
            expect($matches->get(1)->result())->toBe('2');
            expect($matches->get(2)->result())->toBe('3');
            expect($matches->get(5))->toBeNull();
        });

        test('gets all as Match objects', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2');
            $all = $matches->all();

            expect($all)->toHaveCount(2);
            expect($all[0])->toBeInstanceOf(MatchResult::class);
            expect($all[1])->toBeInstanceOf(MatchResult::class);
        });

        test('returns null for first/last when empty', function (): void {
            $matches = Relex::matchAll('/\d+/', 'abc');

            expect($matches->first())->toBeNull();
            expect($matches->last())->toBeNull();
        });
    });

    describe('named captures', function (): void {
        test('extracts named captures as maps', function (): void {
            $matches = Relex::matchAll('/(?<letter>[a-z])(?<number>\d)/', 'a1 b2 c3');
            $captures = $matches->namedCaptures();

            expect($captures)->toHaveCount(3);
            expect($captures[0])->toBe(['letter' => 'a', 'number' => '1']);
            expect($captures[1])->toBe(['letter' => 'b', 'number' => '2']);
        });

        test('plucks specific group from all matches', function (): void {
            $matches = Relex::matchAll('/(?<letter>[a-z])(\d)/', 'a1 b2 c3');

            expect($matches->pluck('letter'))->toBe(['a', 'b', 'c']);
            expect($matches->pluck(2))->toBe(['1', '2', '3']);
        });
    });

    describe('iteration', function (): void {
        test('iterates over matches', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2');
            $results = [];

            foreach ($matches as $match) {
                $results[] = $match->result();
            }

            expect($results)->toBe(['1', '2']);
        });

        test('uses each callback', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3');
            $results = [];

            $matches->each(function (MatchResult $match, int $index) use (&$results): void {
                $results[$index] = $match->result();
            });

            expect($results)->toBe([0 => '1', 1 => '2', 2 => '3']);
        });
    });

    describe('filtering', function (): void {
        test('filters matches', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b22 c333');
            $filtered = $matches->filter(fn (MatchResult $m): bool => mb_strlen((string) $m->result()) > 1);

            expect($filtered->count())->toBe(2);
            expect($filtered->results())->toBe(['22', '333']);
        });
    });

    describe('mapping', function (): void {
        test('maps over matches', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3');
            $doubled = $matches->map(fn (MatchResult $m): int => (int) $m->result() * 2);

            expect($doubled)->toBe([2, 4, 6]);
        });
    });

    describe('reducing', function (): void {
        test('reduces matches to single value', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3');
            $sum = $matches->reduce(fn (int $carry, MatchResult $m): int => $carry + (int) $m->result(), 0);

            expect($sum)->toBe(6);
        });
    });

    describe('taking and skipping', function (): void {
        test('takes first N matches', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3 d4');

            expect($matches->take(2)->results())->toBe(['1', '2']);
        });

        test('skips first N matches', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3 d4');

            expect($matches->skip(2)->results())->toBe(['3', '4']);
        });
    });

    describe('capture modes', function (): void {
        test('captures all (default)', function (): void {
            $matches = Relex::matchAll('/(\w)(\d)/', 'a1 b2');
            $captured = $matches->capture(CaptureMode::All);

            expect($captured->count())->toBe(2);
        });

        test('captures first group only', function (): void {
            $matches = Relex::matchAll('/(\w)(\d)/', 'a1 b2');
            $captured = $matches->capture(CaptureMode::First);
            $all = $captured->all();

            expect($all[0]->groups())->toHaveKey(1);
            expect($all[0]->groups())->not->toHaveKey(0);
        });

        test('captures all but first', function (): void {
            $matches = Relex::matchAll('/(\w)(\d)/', 'a1 b2');
            $captured = $matches->capture(CaptureMode::AllButFirst);
            $all = $captured->all();

            expect($all[0]->groups())->not->toHaveKey(0);
            expect($all[0]->groups())->toHaveKey(1);
            expect($all[0]->groups())->toHaveKey(2);
        });

        test('captures named only', function (): void {
            $matches = Relex::matchAll('/(?<letter>\w)(\d)/', 'a1 b2');
            $captured = $matches->capture(CaptureMode::Named);
            $all = $captured->all();

            expect($all[0]->groups())->toHaveKey('letter');
            expect($all[0]->groups())->not->toHaveKey(0);
        });

        test('captures none', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2');
            $captured = $matches->capture(CaptureMode::None);

            expect($captured->count())->toBe(0);
        });
    });

    describe('Pattern value object', function (): void {
        test('matches using Pattern object', function (): void {
            $pattern = Pattern::create('\d+');
            $matches = Relex::matchAll($pattern, 'a1 b2 c3');

            expect($matches->count())->toBe(3);
        });
    });

    describe('countable', function (): void {
        test('is countable', function (): void {
            $matches = Relex::matchAll('/\d+/', 'a1 b2 c3');

            expect(count($matches))->toBe(3);
        });
    });
});
