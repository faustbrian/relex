<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\ValueObjects;

use ArrayIterator;
use Cline\Relex\Enums\CaptureMode;
use Cline\Relex\Exceptions\MatchException;
use Countable;
use Exception;
use IteratorAggregate;
use Traversable;

use const ARRAY_FILTER_USE_BOTH;
use const PREG_SET_ORDER;
use const PREG_UNMATCHED_AS_NULL;

use function array_filter;
use function array_key_last;
use function array_map;
use function array_reduce;
use function array_slice;
use function array_values;
use function count;
use function is_string;
use function preg_last_error_msg;
use function preg_match_all;

/**
 * Represents the result of a regex match all operation.
 *
 * This class wraps the result of preg_match_all and provides a clean API
 * for iterating over matches, accessing captures, and filtering results.
 *
 * @author Brian Faust <brian@cline.sh>
 * @implements IteratorAggregate<int, MatchResult>
 * @psalm-immutable
 */
final readonly class MatchAllResult implements Countable, IteratorAggregate
{
    /**
     * @param string                                     $pattern  The pattern that was matched
     * @param string                                     $subject  The subject string that was searched
     * @param bool                                       $hasMatch Whether any matches were found
     * @param array<int, array<int|string, null|string>> $matches  The raw matches array
     * @param int<0, max>                                $count    Number of matches found
     */
    public function __construct(
        private string $pattern,
        private string $subject,
        private bool $hasMatch,
        private array $matches = [],
        private int $count = 0,
    ) {}

    /**
     * Create a MatchAll result from a preg_match_all operation.
     *
     * @param Pattern|string $pattern The regex pattern
     * @param string         $subject The subject string to match against
     * @param int            $flags   Additional PREG flags
     * @param int            $offset  Offset in subject to start matching
     */
    public static function for(string|Pattern $pattern, string $subject, int $flags = 0, int $offset = 0): self
    {
        $patternString = $pattern instanceof Pattern ? $pattern->toString() : $pattern;
        $matches = [];

        // Use PREG_SET_ORDER for easier iteration and PREG_UNMATCHED_AS_NULL
        $flags |= PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL;

        try {
            $result = preg_match_all($patternString, $subject, $matches, $flags, $offset);
        } catch (Exception $exception) {
            throw MatchException::matchAllFailed($patternString, $subject, $exception->getMessage());
        }

        if ($result === false) {
            throw MatchException::matchAllFailed($patternString, $subject, preg_last_error_msg());
        }

        /** @var array<int, array<int|string, null|string>> $matches */
        return new self($patternString, $subject, $result > 0, $matches, $result);
    }

    /**
     * Check if any matches were found.
     */
    public function hasMatch(): bool
    {
        return $this->hasMatch;
    }

    /**
     * Check if no matches were found.
     */
    public function isEmpty(): bool
    {
        return !$this->hasMatch;
    }

    /**
     * Get the number of matches found.
     *
     * @return int<0, max>
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Get all full match results (group 0 from each match).
     *
     * @return array<int, string>
     */
    public function results(): array
    {
        return array_map(
            fn (array $result): string => $result[0] ?? '',
            $this->matches,
        );
    }

    /**
     * Get the first match result.
     */
    public function first(): ?MatchResult
    {
        if ($this->matches === []) {
            return null;
        }

        return new MatchResult($this->pattern, $this->subject, true, $this->matches[0]);
    }

    /**
     * Get the last match result.
     */
    public function last(): ?MatchResult
    {
        if ($this->matches === []) {
            return null;
        }

        $lastMatch = $this->matches[array_key_last($this->matches)];

        return new MatchResult($this->pattern, $this->subject, true, $lastMatch);
    }

    /**
     * Get a specific match by index.
     */
    public function get(int $index): ?MatchResult
    {
        if (!isset($this->matches[$index])) {
            return null;
        }

        return new MatchResult($this->pattern, $this->subject, true, $this->matches[$index]);
    }

    /**
     * Get all matches as MatchResult objects.
     *
     * @return array<int, MatchResult>
     */
    public function all(): array
    {
        return array_map(
            fn (array $result): MatchResult => new MatchResult($this->pattern, $this->subject, true, $result),
            $this->matches,
        );
    }

    /**
     * Get an iterator over MatchResult objects.
     *
     * @return Traversable<int, MatchResult>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Get all named captures as an array of associative arrays.
     *
     * @return array<int, array<string, null|string>>
     */
    public function namedCaptures(): array
    {
        return array_map(
            fn (array $result): array => array_filter(
                $result,
                fn (mixed $value, int|string $key): bool => is_string($key),
                ARRAY_FILTER_USE_BOTH,
            ),
            $this->matches,
        );
    }

    /**
     * Get a specific group from all matches.
     *
     * @return array<int, null|string>
     */
    public function pluck(int|string $group): array
    {
        return array_map(
            fn (array $result): ?string => $result[$group] ?? null,
            $this->matches,
        );
    }

    /**
     * Filter matches using a callback.
     *
     * @param callable(MatchResult): bool $callback
     */
    public function filter(callable $callback): self
    {
        $filtered = array_filter(
            $this->matches,
            fn (array $result): bool => $callback(new MatchResult($this->pattern, $this->subject, true, $result)),
        );

        return new self(
            $this->pattern,
            $this->subject,
            $filtered !== [],
            array_values($filtered),
            count($filtered),
        );
    }

    /**
     * Map over matches using a callback.
     *
     * @template T
     *
     * @param callable(MatchResult): T $callback
     *
     * @return array<int, T>
     */
    public function map(callable $callback): array
    {
        return array_map(
            fn (array $result): mixed => $callback(new MatchResult($this->pattern, $this->subject, true, $result)),
            $this->matches,
        );
    }

    /**
     * Execute a callback for each match.
     *
     * @param callable(MatchResult, int): void $callback
     */
    public function each(callable $callback): self
    {
        foreach ($this->matches as $index => $result) {
            $callback(new MatchResult($this->pattern, $this->subject, true, $result), $index);
        }

        return $this;
    }

    /**
     * Reduce matches to a single value.
     *
     * @template T
     *
     * @param callable(T, MatchResult): T $callback
     * @param T                           $initial
     *
     * @return T
     */
    public function reduce(callable $callback, mixed $initial): mixed
    {
        return array_reduce(
            $this->matches,
            fn (mixed $carry, array $result): mixed => $callback(
                $carry,
                new MatchResult($this->pattern, $this->subject, true, $result),
            ),
            $initial,
        );
    }

    /**
     * Take the first N matches.
     */
    public function take(int $count): self
    {
        $taken = array_slice($this->matches, 0, $count);

        return new self(
            $this->pattern,
            $this->subject,
            $taken !== [],
            $taken,
            count($taken),
        );
    }

    /**
     * Skip the first N matches.
     */
    public function skip(int $count): self
    {
        $skipped = array_slice($this->matches, $count);

        return new self(
            $this->pattern,
            $this->subject,
            $skipped !== [],
            $skipped,
            count($skipped),
        );
    }

    /**
     * Apply a capture mode to filter results.
     */
    public function capture(CaptureMode $mode): self
    {
        $filtered = match ($mode) {
            CaptureMode::All => $this->matches,
            CaptureMode::First => array_map(
                fn (array $m): array => isset($m[1]) ? [1 => $m[1]] : [],
                $this->matches,
            ),
            CaptureMode::AllButFirst => array_map(
                fn (array $m): array => array_filter(
                    $m,
                    fn (mixed $v, int|string $k): bool => $k !== 0,
                    ARRAY_FILTER_USE_BOTH,
                ),
                $this->matches,
            ),
            CaptureMode::Named => $this->namedCaptures(),
            CaptureMode::None => [],
        };

        return new self(
            $this->pattern,
            $this->subject,
            $filtered !== [],
            $filtered,
            count($filtered),
        );
    }

    /**
     * Get the original pattern.
     */
    public function pattern(): string
    {
        return $this->pattern;
    }

    /**
     * Get the original subject string.
     */
    public function subject(): string
    {
        return $this->subject;
    }
}
