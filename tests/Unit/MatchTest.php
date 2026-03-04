<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Exceptions\GroupNotFoundException;
use Cline\Relex\Exceptions\MatchException;
use Cline\Relex\Relex;
use Cline\Relex\ValueObjects\MatchResult;
use Cline\Relex\ValueObjects\Pattern;

describe('Match', function (): void {
    describe('basic matching', function (): void {
        test('matches simple pattern', function (): void {
            $match = Relex::match('/\d+/', 'abc 123 def');

            expect($match->hasMatch())->toBeTrue();
            expect($match->result())->toBe('123');
        });

        test('returns null for no match', function (): void {
            $match = Relex::match('/\d+/', 'no numbers here');

            expect($match->hasMatch())->toBeFalse();
            expect($match->result())->toBeNull();
        });

        test('returns default value when no match', function (): void {
            $match = Relex::match('/\d+/', 'no numbers here');

            expect($match->resultOr('default'))->toBe('default');
        });

        test('failed method is inverse of hasMatch', function (): void {
            $matched = Relex::match('/\d+/', '123');
            $failed = Relex::match('/\d+/', 'abc');

            expect($matched->failed())->toBeFalse();
            expect($failed->failed())->toBeTrue();
        });
    });

    describe('capture groups', function (): void {
        test('accesses group by index', function (): void {
            $match = Relex::match('/(\d+)-(\d+)/', '123-456');

            expect($match->group(0))->toBe('123-456');
            expect($match->group(1))->toBe('123');
            expect($match->group(2))->toBe('456');
        });

        test('accesses named group', function (): void {
            $match = Relex::match('/(?<year>\d{4})-(?<month>\d{2})/', '2024-12');

            expect($match->group('year'))->toBe('2024');
            expect($match->group('month'))->toBe('12');
        });

        test('throws exception for non-existent index group', function (): void {
            $match = Relex::match('/(\d+)/', '123');
            $match->group(5);
        })->throws(GroupNotFoundException::class);

        test('throws exception for non-existent named group', function (): void {
            $match = Relex::match('/(?<year>\d+)/', '2024');
            $match->group('month');
        })->throws(GroupNotFoundException::class);

        test('returns default for missing group', function (): void {
            $match = Relex::match('/(\d+)/', '123');

            expect($match->groupOr(5, 'default'))->toBe('default');
            expect($match->groupOr('missing', 'default'))->toBe('default');
        });

        test('checks if group exists', function (): void {
            $match = Relex::match('/(?<year>\d+)/', '2024');

            expect($match->hasGroup(0))->toBeTrue();
            expect($match->hasGroup(1))->toBeTrue();
            expect($match->hasGroup('year'))->toBeTrue();
            expect($match->hasGroup('month'))->toBeFalse();
            expect($match->hasGroup(5))->toBeFalse();
        });

        test('checks if group matched', function (): void {
            $match = Relex::match('/(\d+)?-(\d+)/', '-456');

            expect($match->groupMatched(1))->toBeFalse(); // Optional group didn't match
            expect($match->groupMatched(2))->toBeTrue();
        });
    });

    describe('groups collections', function (): void {
        test('gets all groups', function (): void {
            $match = Relex::match('/(\d+)-(\d+)/', '123-456');
            $groups = $match->groups();

            expect($groups)->toHaveCount(3);
            expect($groups[0])->toBe('123-456');
            expect($groups[1])->toBe('123');
            expect($groups[2])->toBe('456');
        });

        test('gets named groups only', function (): void {
            $match = Relex::match('/(?<year>\d{4})-(?<month>\d{2})/', '2024-12');
            $named = $match->namedGroups();

            expect($named)->toHaveKey('year');
            expect($named)->toHaveKey('month');
            expect($named)->not->toHaveKey(0);
            expect($named['year'])->toBe('2024');
        });

        test('gets indexed groups only', function (): void {
            $match = Relex::match('/(?<year>\d{4})-(\d{2})/', '2024-12');
            $indexed = $match->indexedGroups();

            expect($indexed)->toHaveKey(0);
            expect($indexed)->toHaveKey(1);
            expect($indexed)->toHaveKey(2);
            expect($indexed)->not->toHaveKey('year');
        });
    });

    describe('offset capture', function (): void {
        test('captures positions with offsets', function (): void {
            $match = Relex::matchWithOffsets('/\d+/', 'abc 123 def');
            $position = $match->position();

            expect($position)->not->toBeNull();
            expect($position->start())->toBe(4);
            expect($position->length())->toBe(3);
            expect($position->end())->toBe(7);
        });

        test('gets position for specific group', function (): void {
            $match = Relex::matchWithOffsets('/(\d+)-(\d+)/', 'abc 123-456 def');

            expect($match->position(0)->start())->toBe(4);
            expect($match->position(1)->start())->toBe(4);
            expect($match->position(2)->start())->toBe(8);
        });

        test('returns null position for unmatched optional group', function (): void {
            $match = Relex::matchWithOffsets('/(\d+)?-(\d+)/', 'abc -456 def');

            expect($match->position(1))->toBeNull();
            expect($match->position(2))->not->toBeNull();
        });
    });

    describe('Pattern value object', function (): void {
        test('matches using Pattern object', function (): void {
            $pattern = Pattern::create('\d+')->caseInsensitive();
            $match = Relex::match($pattern, 'ABC 123');

            expect($match->hasMatch())->toBeTrue();
            expect($match->result())->toBe('123');
        });
    });

    describe('offset parameter', function (): void {
        test('starts matching at offset', function (): void {
            $match = Relex::match('/\d+/', 'a1 b2 c3', 3);

            expect($match->result())->toBe('2');
        });
    });

    describe('callbacks', function (): void {
        test('maps result with callback', function (): void {
            $match = Relex::match('/(\d+)/', '123');
            $result = $match->map(fn (MatchResult $m): int => (int) $m->result() * 2);

            expect($result)->toBe(246);
        });

        test('map returns null when no match', function (): void {
            $match = Relex::match('/\d+/', 'abc');
            $result = $match->map(fn (MatchResult $m): int => (int) $m->result());

            expect($result)->toBeNull();
        });

        test('executes callback when matched', function (): void {
            $called = false;
            $match = Relex::match('/\d+/', '123');

            $match->whenMatched(function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });

        test('does not execute whenMatched callback when no match', function (): void {
            $called = false;
            $match = Relex::match('/\d+/', 'abc');

            $match->whenMatched(function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        test('executes callback when failed', function (): void {
            $called = false;
            $match = Relex::match('/\d+/', 'abc');

            $match->whenFailed(function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });
    });

    describe('error handling', function (): void {
        test('throws exception for invalid pattern', function (): void {
            Relex::match('/[invalid/', 'test');
        })->throws(MatchException::class);
    });

    describe('metadata', function (): void {
        test('returns original pattern', function (): void {
            $match = Relex::match('/\d+/', 'test 123');

            expect($match->pattern())->toBe('/\d+/');
        });

        test('returns original subject', function (): void {
            $match = Relex::match('/\d+/', 'test 123');

            expect($match->subject())->toBe('test 123');
        });
    });
});
