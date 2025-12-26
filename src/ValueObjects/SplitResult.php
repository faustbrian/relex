<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\ValueObjects;

use ArrayIterator;
use Cline\Relex\Exceptions\SplitException;
use Countable;
use Exception;
use IteratorAggregate;
use Traversable;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

use function array_filter;
use function array_key_last;
use function array_map;
use function array_reverse;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function mb_strrpos;
use function mb_substr;
use function preg_last_error_msg;
use function preg_split;
use function str_starts_with;

/**
 * Represents the result of a regex split operation.
 *
 * This class wraps the result of preg_split and provides a clean API
 * for accessing the split segments and working with delimiters.
 *
 * @author Brian Faust <brian@cline.sh>
 * @implements IteratorAggregate<int, string>
 * @psalm-immutable
 */
final readonly class SplitResult implements Countable, IteratorAggregate
{
    /**
     * @param string        $pattern  The pattern used for splitting
     * @param string        $subject  The original subject string
     * @param array<string> $segments The resulting segments
     * @param int           $limit    The limit that was used
     */
    public function __construct(
        private string $pattern,
        private string $subject,
        private array $segments,
        private int $limit = -1,
    ) {}

    /**
     * Create a Split result from a preg_split operation.
     *
     * @param Pattern|string $pattern The regex pattern to split on
     * @param string         $subject The subject string to split
     * @param int            $limit   Maximum number of segments (-1 for unlimited)
     * @param int            $flags   Additional PREG_SPLIT flags
     */
    public static function for(string|Pattern $pattern, string $subject, int $limit = -1, int $flags = 0): self
    {
        $patternString = $pattern instanceof Pattern ? $pattern->toString() : $pattern;

        // Remove empty segments by default
        $flags |= PREG_SPLIT_NO_EMPTY;

        try {
            $result = preg_split($patternString, $subject, $limit, $flags);
        } catch (Exception $exception) {
            throw SplitException::failed($patternString, $subject, $exception->getMessage());
        }

        if ($result === false) {
            throw SplitException::failed($patternString, $subject, preg_last_error_msg());
        }

        /** @var array<string> $result */
        return new self($patternString, $subject, $result, $limit);
    }

    /**
     * Create a Split result keeping delimiters in the output.
     *
     * @param Pattern|string $pattern The regex pattern to split on
     * @param string         $subject The subject string to split
     * @param int            $limit   Maximum number of segments (-1 for unlimited)
     */
    public static function withDelimiters(string|Pattern $pattern, string $subject, int $limit = -1): self
    {
        $patternString = $pattern instanceof Pattern ? $pattern->toString() : $pattern;

        // Wrap pattern in capturing group if not already
        $wrappedPattern = self::ensureCapturingDelimiter($patternString);

        $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;

        try {
            $result = preg_split($wrappedPattern, $subject, $limit, $flags);
        } catch (Exception $exception) {
            throw SplitException::failed($patternString, $subject, $exception->getMessage());
        }

        if ($result === false) {
            throw SplitException::failed($patternString, $subject, preg_last_error_msg());
        }

        /** @var array<string> $result */
        return new self($patternString, $subject, $result, $limit);
    }

    /**
     * Get the resulting segments.
     *
     * @return array<string>
     */
    public function results(): array
    {
        return $this->segments;
    }

    /**
     * Get the segments as a string array (alias for results).
     *
     * @return array<string>
     */
    public function toArray(): array
    {
        return $this->segments;
    }

    /**
     * Get the number of segments.
     */
    public function count(): int
    {
        return count($this->segments);
    }

    /**
     * Check if the split produced any segments.
     */
    public function isEmpty(): bool
    {
        return $this->segments === [];
    }

    /**
     * Check if the split produced segments.
     */
    public function isNotEmpty(): bool
    {
        return $this->segments !== [];
    }

    /**
     * Get the first segment.
     */
    public function first(): ?string
    {
        return $this->segments[0] ?? null;
    }

    /**
     * Get the last segment.
     */
    public function last(): ?string
    {
        if ($this->segments === []) {
            return null;
        }

        return $this->segments[array_key_last($this->segments)];
    }

    /**
     * Get a specific segment by index.
     */
    public function get(int $index): ?string
    {
        return $this->segments[$index] ?? null;
    }

    /**
     * Get an iterator over segments.
     *
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->segments);
    }

    /**
     * Filter segments using a callback.
     *
     * @param callable(string): bool $callback
     */
    public function filter(callable $callback): self
    {
        return new self(
            $this->pattern,
            $this->subject,
            array_values(array_filter($this->segments, $callback)),
            $this->limit,
        );
    }

    /**
     * Map over segments using a callback.
     *
     * @template T
     *
     * @param callable(string): T $callback
     *
     * @return array<int, T>
     */
    public function map(callable $callback): array
    {
        return array_values(array_map($callback, $this->segments));
    }

    /**
     * Execute a callback for each segment.
     *
     * @param callable(string, int): void $callback
     */
    public function each(callable $callback): self
    {
        foreach ($this->segments as $index => $segment) {
            $callback($segment, $index);
        }

        return $this;
    }

    /**
     * Join segments back together with a separator.
     */
    public function join(string $separator = ''): string
    {
        return implode($separator, $this->segments);
    }

    /**
     * Take the first N segments.
     */
    public function take(int $count): self
    {
        return new self(
            $this->pattern,
            $this->subject,
            array_slice($this->segments, 0, $count),
            $this->limit,
        );
    }

    /**
     * Skip the first N segments.
     */
    public function skip(int $count): self
    {
        return new self(
            $this->pattern,
            $this->subject,
            array_slice($this->segments, $count),
            $this->limit,
        );
    }

    /**
     * Reverse the order of segments.
     */
    public function reverse(): self
    {
        return new self(
            $this->pattern,
            $this->subject,
            array_reverse($this->segments),
            $this->limit,
        );
    }

    /**
     * Get unique segments.
     */
    public function unique(): self
    {
        return new self(
            $this->pattern,
            $this->subject,
            array_values(array_unique($this->segments)),
            $this->limit,
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

    /**
     * Ensure the pattern has a capturing group for delimiter capture.
     */
    private static function ensureCapturingDelimiter(string $pattern): string
    {
        // Extract delimiter and modifiers
        $delimiter = $pattern[0];
        $lastDelimiterPos = mb_strrpos($pattern, $delimiter, 1);

        if ($lastDelimiterPos === false) {
            return $pattern;
        }

        $expression = mb_substr($pattern, 1, $lastDelimiterPos - 1);
        $modifiers = mb_substr($pattern, $lastDelimiterPos + 1);

        // Wrap expression in capturing group if not already grouped
        if (!str_starts_with($expression, '(')) {
            $expression = '('.$expression.')';
        }

        return $delimiter.$expression.$delimiter.$modifiers;
    }
}
