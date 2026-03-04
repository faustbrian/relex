<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex;

use Cline\Relex\ValueObjects\MatchAllResult;
use Cline\Relex\ValueObjects\MatchResult;
use Cline\Relex\ValueObjects\Pattern;
use Cline\Relex\ValueObjects\ReplaceResult;
use Cline\Relex\ValueObjects\SplitResult;

use const PREG_OFFSET_CAPTURE;

use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function preg_quote;

/**
 * Main entry point for Relex regex operations.
 *
 * Provides a clean, fluent API for common regex operations with support for
 * both simple string patterns and complex Pattern value objects.
 *
 * @example Basic usage
 * ```php
 * // Simple matching
 * Relex::match('/\d+/', 'abc 123 def')->result(); // "123"
 *
 * // Match with named groups
 * Relex::match('/(?<year>\d{4})-(?<month>\d{2})/', '2024-12')
 *     ->namedGroups(); // ['year' => '2024', 'month' => '12']
 *
 * // Match all occurrences
 * Relex::matchAll('/\d+/', 'a1 b2 c3')->results(); // ["1", "2", "3"]
 *
 * // Replace with callback
 * Relex::replace('/\d+/', fn($m) => $m->result() * 2, 'a1 b2')->result(); // "a2 b4"
 *
 * // Split string
 * Relex::split('/\s+/', 'a b  c')->results(); // ["a", "b", "c"]
 * ```
 *
 * @example Using Pattern value object
 * ```php
 * $pattern = Pattern::create('\d+')->caseInsensitive()->utf8();
 *
 * Relex::match($pattern, 'ABC 123')->result(); // "123"
 * ```
 * @author Brian Faust <brian@cline.sh>
 */
final class Relex
{
    /**
     * Match a pattern against a subject string.
     *
     * Returns a Match object containing the first match and captured groups.
     *
     * @param Pattern|string $pattern The regex pattern (with delimiters) or Pattern object
     * @param string         $subject The string to match against
     * @param int            $offset  Offset in subject to start matching (in bytes)
     */
    public static function match(string|Pattern $pattern, string $subject, int $offset = 0): MatchResult
    {
        return MatchResult::for($pattern, $subject, 0, $offset);
    }

    /**
     * Match a pattern and include byte offsets in the result.
     *
     * @param Pattern|string $pattern The regex pattern (with delimiters) or Pattern object
     * @param string         $subject The string to match against
     * @param int            $offset  Offset in subject to start matching (in bytes)
     */
    public static function matchWithOffsets(string|Pattern $pattern, string $subject, int $offset = 0): MatchResult
    {
        return MatchResult::for($pattern, $subject, PREG_OFFSET_CAPTURE, $offset);
    }

    /**
     * Find all matches of a pattern in a subject string.
     *
     * Returns a MatchAll object containing all matches.
     *
     * @param Pattern|string $pattern The regex pattern (with delimiters) or Pattern object
     * @param string         $subject The string to search
     * @param int            $offset  Offset in subject to start matching (in bytes)
     */
    public static function matchAll(string|Pattern $pattern, string $subject, int $offset = 0): MatchAllResult
    {
        return MatchAllResult::for($pattern, $subject, 0, $offset);
    }

    /**
     * Test if a pattern matches a subject string.
     *
     * More efficient than match() when you only need a boolean result.
     *
     * @param Pattern|string $pattern The regex pattern (with delimiters) or Pattern object
     * @param string         $subject The string to test
     * @param int            $offset  Offset in subject to start matching (in bytes)
     */
    public static function test(string|Pattern $pattern, string $subject, int $offset = 0): bool
    {
        return self::match($pattern, $subject, $offset)->hasMatch();
    }

    /**
     * Replace occurrences of a pattern in a subject string.
     *
     * @param array<Pattern|string>|Pattern|string $pattern     The regex pattern(s)
     * @param array<string>|callable|string        $replacement The replacement string, array, or callback
     * @param array<string>|string                 $subject     The subject string(s)
     * @param int                                  $limit       Maximum replacements (-1 for unlimited)
     */
    public static function replace(
        string|Pattern|array $pattern,
        string|array|callable $replacement,
        string|array $subject,
        int $limit = -1,
    ): ReplaceResult {
        return ReplaceResult::for($pattern, $replacement, $subject, $limit);
    }

    /**
     * Replace the first occurrence of a pattern.
     *
     * @param Pattern|string  $pattern     The regex pattern
     * @param callable|string $replacement The replacement string or callback
     * @param string          $subject     The subject string
     */
    public static function replaceFirst(
        string|Pattern $pattern,
        string|callable $replacement,
        string $subject,
    ): ReplaceResult {
        return ReplaceResult::for($pattern, $replacement, $subject, 1);
    }

    /**
     * Split a string by a pattern.
     *
     * @param Pattern|string $pattern The regex pattern to split on
     * @param string         $subject The string to split
     * @param int            $limit   Maximum number of segments (-1 for unlimited)
     */
    public static function split(string|Pattern $pattern, string $subject, int $limit = -1): SplitResult
    {
        return SplitResult::for($pattern, $subject, $limit);
    }

    /**
     * Split a string by a pattern, keeping the delimiters in the result.
     *
     * @param Pattern|string $pattern The regex pattern to split on
     * @param string         $subject The string to split
     * @param int            $limit   Maximum number of segments (-1 for unlimited)
     */
    public static function splitWithDelimiters(string|Pattern $pattern, string $subject, int $limit = -1): SplitResult
    {
        return SplitResult::withDelimiters($pattern, $subject, $limit);
    }

    /**
     * Create a compiled Pattern object for reuse.
     *
     * @param string $expression The regex expression (without delimiters)
     * @param string $delimiter  The delimiter to use (default: /)
     */
    public static function compile(string $expression, string $delimiter = '/'): Pattern
    {
        return Pattern::create($expression, $delimiter);
    }

    /**
     * Create a Pattern object from a complete pattern string.
     *
     * @param string $pattern Complete pattern like "/\d+/im"
     */
    public static function pattern(string $pattern): Pattern
    {
        return Pattern::from($pattern);
    }

    /**
     * Validate that a pattern is syntactically correct.
     *
     * @param string $pattern The pattern to validate
     *
     * @throws Exceptions\PatternCompilationException If invalid
     */
    public static function validate(string $pattern): void
    {
        Pattern::validate($pattern);
    }

    /**
     * Check if a pattern is syntactically valid.
     *
     * @param string $pattern The pattern to check
     */
    public static function isValid(string $pattern): bool
    {
        return Pattern::isValid($pattern);
    }

    /**
     * Escape special regex characters in a string.
     *
     * Makes a string safe to use as a literal match in a regex pattern.
     *
     * @param string      $value     The string to escape
     * @param null|string $delimiter Additional delimiter to escape (usually /)
     */
    public static function escape(string $value, ?string $delimiter = '/'): string
    {
        return preg_quote($value, $delimiter);
    }

    /**
     * Create a pattern that matches any of the given strings literally.
     *
     * @param array<string> $strings   The strings to match
     * @param string        $delimiter The delimiter to use
     */
    public static function any(array $strings, string $delimiter = '/'): Pattern
    {
        $escaped = array_map(fn (string $s): string => preg_quote($s, $delimiter), $strings);

        return Pattern::create(implode('|', $escaped), $delimiter);
    }

    /**
     * Count the number of matches of a pattern in a subject.
     *
     * @param Pattern|string $pattern The regex pattern
     * @param string         $subject The string to search
     */
    public static function count(string|Pattern $pattern, string $subject): int
    {
        return self::matchAll($pattern, $subject)->count();
    }

    /**
     * Extract all matches of a pattern as a simple array of strings.
     *
     * @param Pattern|string $pattern The regex pattern
     * @param string         $subject The string to search
     *
     * @return array<string>
     */
    public static function extract(string|Pattern $pattern, string $subject): array
    {
        return self::matchAll($pattern, $subject)->results();
    }

    /**
     * Extract a specific named group from all matches.
     *
     * @param Pattern|string $pattern The regex pattern with named groups
     * @param string         $subject The string to search
     * @param string         $group   The name of the group to extract
     *
     * @return array<null|string>
     */
    public static function pluck(string|Pattern $pattern, string $subject, string $group): array
    {
        return self::matchAll($pattern, $subject)->pluck($group);
    }

    /**
     * Filter an array of strings, keeping only those matching a pattern.
     *
     * @param Pattern|string $pattern The regex pattern
     * @param array<string>  $strings The strings to filter
     *
     * @return array<string>
     */
    public static function filter(string|Pattern $pattern, array $strings): array
    {
        return array_values(array_filter(
            $strings,
            fn (string $s): bool => self::test($pattern, $s),
        ));
    }

    /**
     * Reject strings from an array that match a pattern.
     *
     * @param Pattern|string $pattern The regex pattern
     * @param array<string>  $strings The strings to filter
     *
     * @return array<string>
     */
    public static function reject(string|Pattern $pattern, array $strings): array
    {
        return array_values(array_filter(
            $strings,
            fn (string $s): bool => !self::test($pattern, $s),
        ));
    }

    /**
     * Get the first string from an array that matches a pattern.
     *
     * @param Pattern|string $pattern The regex pattern
     * @param array<string>  $strings The strings to search
     */
    public static function first(string|Pattern $pattern, array $strings): ?string
    {
        foreach ($strings as $string) {
            if (self::test($pattern, $string)) {
                return $string;
            }
        }

        return null;
    }
}
