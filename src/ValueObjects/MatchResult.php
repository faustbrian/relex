<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\ValueObjects;

use Cline\Relex\Exceptions\GroupNotFoundException;
use Cline\Relex\Exceptions\MatchException;
use Cline\Relex\Support\Position;
use Exception;

use const ARRAY_FILTER_USE_BOTH;
use const PREG_OFFSET_CAPTURE;
use const PREG_UNMATCHED_AS_NULL;

use function array_filter;
use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;
use function preg_last_error_msg;
use function preg_match;

/**
 * Represents the result of a single regex match operation.
 *
 * This class wraps the result of preg_match and provides a clean API
 * for accessing matched groups, positions, and named captures.
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class MatchResult
{
    /**
     * @param string                           $pattern   The pattern that was matched
     * @param string                           $subject   The subject string that was searched
     * @param bool                             $hasMatch  Whether a match was found
     * @param array<int|string, null|string>   $matches   The captured groups
     * @param null|array<int|string, Position> $positions The positions of captures (if offset capture was used)
     */
    public function __construct(
        private string $pattern,
        private string $subject,
        private bool $hasMatch,
        private array $matches = [],
        private ?array $positions = null,
    ) {}

    /**
     * Create a Match result from a preg_match operation.
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

        // Always use PREG_UNMATCHED_AS_NULL for consistent behavior
        $flags |= PREG_UNMATCHED_AS_NULL;
        $captureOffsets = ($flags & PREG_OFFSET_CAPTURE) !== 0;

        try {
            /** @var 0|256|512|768 $flags */
            $result = preg_match($patternString, $subject, $matches, $flags, $offset);
        } catch (Exception $exception) {
            throw MatchException::matchFailed($patternString, $subject, $exception->getMessage());
        }

        if ($result === false) {
            throw MatchException::matchFailed($patternString, $subject, preg_last_error_msg());
        }

        $positions = null;

        if ($captureOffsets && $matches !== []) {
            $positions = [];
            $normalizedMatches = [];

            foreach ($matches as $key => $value) {
                if (is_array($value)) {
                    $normalizedMatches[$key] = $value[0];
                    $positions[$key] = $value[0] !== null
                        ? Position::fromOffsetCapture($value)
                        : new Position(-1, 0);
                } else {
                    $normalizedMatches[$key] = $value;
                }
            }

            $matches = $normalizedMatches;
        }

        /** @var array<int|string, null|string> $matches */
        return new self($patternString, $subject, $result === 1, $matches, $positions);
    }

    /**
     * Check if the pattern matched.
     */
    public function hasMatch(): bool
    {
        return $this->hasMatch;
    }

    /**
     * Check if the pattern did not match.
     */
    public function failed(): bool
    {
        return !$this->hasMatch;
    }

    /**
     * Get the full match result (group 0).
     */
    public function result(): ?string
    {
        return $this->matches[0] ?? null;
    }

    /**
     * Get the full match result or a default value.
     */
    public function resultOr(string $default): string
    {
        return $this->result() ?? $default;
    }

    /**
     * Get a specific capture group by index or name.
     *
     * @throws GroupNotFoundException If the group doesn't exist
     */
    public function group(int|string $group): ?string
    {
        if (!array_key_exists($group, $this->matches)) {
            if (is_int($group)) {
                throw GroupNotFoundException::byIndex($group, $this->pattern);
            }

            throw GroupNotFoundException::byName($group, $this->pattern);
        }

        return $this->matches[$group];
    }

    /**
     * Get a specific capture group or a default value.
     */
    public function groupOr(int|string $group, string $default): string
    {
        try {
            return $this->group($group) ?? $default;
        } catch (GroupNotFoundException) {
            return $default;
        }
    }

    /**
     * Check if a specific group exists.
     */
    public function hasGroup(int|string $group): bool
    {
        return array_key_exists($group, $this->matches);
    }

    /**
     * Check if a specific group matched (exists and is not null).
     */
    public function groupMatched(int|string $group): bool
    {
        return $this->hasGroup($group) && $this->matches[$group] !== null;
    }

    /**
     * Get all captured groups (including the full match at index 0).
     *
     * @return array<int|string, null|string>
     */
    public function groups(): array
    {
        return $this->matches;
    }

    /**
     * Get only named capture groups as an associative array.
     *
     * @return array<string, null|string>
     */
    public function namedGroups(): array
    {
        return array_filter(
            $this->matches,
            fn (mixed $value, int|string $key): bool => is_string($key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Get only indexed (numeric) capture groups.
     *
     * @return array<int, null|string>
     */
    public function indexedGroups(): array
    {
        return array_filter(
            $this->matches,
            fn (mixed $value, int|string $key): bool => is_int($key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Get the position of a capture group.
     *
     * @throws GroupNotFoundException If the group doesn't exist
     */
    public function position(int|string $group = 0): ?Position
    {
        if ($this->positions === null) {
            return null;
        }

        if (!array_key_exists($group, $this->positions)) {
            if (is_int($group)) {
                throw GroupNotFoundException::byIndex($group, $this->pattern);
            }

            throw GroupNotFoundException::byName($group, $this->pattern);
        }

        $position = $this->positions[$group];

        return $position->isValid() ? $position : null;
    }

    /**
     * Get all positions (if offset capture was enabled).
     *
     * @return null|array<int|string, Position>
     */
    public function positions(): ?array
    {
        return $this->positions;
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
     * Transform the match result using a callback.
     *
     * @template T
     *
     * @param callable(self): T $callback
     *
     * @return null|T Returns null if no match
     */
    public function map(callable $callback): mixed
    {
        if (!$this->hasMatch) {
            return null;
        }

        return $callback($this);
    }

    /**
     * Execute a callback if a match was found.
     *
     * @param callable(self): void $callback
     */
    public function whenMatched(callable $callback): self
    {
        if ($this->hasMatch) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Execute a callback if no match was found.
     *
     * @param callable(): void $callback
     */
    public function whenFailed(callable $callback): self
    {
        if (!$this->hasMatch) {
            $callback();
        }

        return $this;
    }
}
