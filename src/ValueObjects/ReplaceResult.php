<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\ValueObjects;

use Cline\Relex\Exceptions\ReplaceException;
use Exception;
use Stringable;

use const PREG_UNMATCHED_AS_NULL;

use function array_map;
use function implode;
use function is_array;
use function is_callable;
use function is_string;
use function preg_last_error_msg;
use function preg_replace;
use function preg_replace_callback;

/**
 * Represents the result of a regex replace operation.
 *
 * This class wraps the result of preg_replace and preg_replace_callback,
 * providing a clean API for accessing the result and replacement count.
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class ReplaceResult
{
    /**
     * @param array<string>|string $pattern     The pattern(s) used for replacement
     * @param mixed                $replacement The replacement string/callback
     * @param array<string>|string $subject     The original subject(s)
     * @param array<string>|string $result      The result after replacement
     * @param int                  $count       Number of replacements made
     */
    public function __construct(
        private string|array $pattern,
        private mixed $replacement,
        private string|array $subject,
        private string|array $result,
        private int $count,
    ) {}

    /**
     * Create a Replace result from a preg_replace operation.
     *
     * @param array<Pattern|string>|Pattern|string $pattern     The regex pattern(s)
     * @param array<string>|callable|string        $replacement The replacement string, array, or callback
     * @param array<string>|string                 $subject     The subject string(s)
     * @param int                                  $limit       Maximum replacements (-1 for unlimited)
     */
    public static function for(
        string|Pattern|array $pattern,
        string|array|callable $replacement,
        string|array $subject,
        int $limit = -1,
    ): self {
        $patternStrings = self::normalizePatterns($pattern);
        $count = 0;

        try {
            if (is_callable($replacement) && !is_string($replacement) && !is_array($replacement)) {
                $result = self::replaceWithCallback($patternStrings, $replacement, $subject, $limit, $count);
            } else {
                /** @var array<string>|string $replacement */
                $result = preg_replace($patternStrings, $replacement, $subject, $limit, $count);
            }
        } catch (Exception $exception) {
            $patternDesc = is_array($patternStrings) ? implode(', ', $patternStrings) : $patternStrings;
            $subjectDesc = is_array($subject) ? '[array]' : $subject;

            throw ReplaceException::failed($patternDesc, $subjectDesc, $exception->getMessage());
        }

        if ($result === null) {
            $patternDesc = is_array($patternStrings) ? implode(', ', $patternStrings) : $patternStrings;
            $subjectDesc = is_array($subject) ? '[array]' : $subject;

            throw ReplaceException::failed($patternDesc, $subjectDesc, preg_last_error_msg());
        }

        /** @var array<string>|string $result */
        return new self($patternStrings, $replacement, $subject, $result, $count);
    }

    /**
     * Get the result of the replacement.
     *
     * @return array<string>|string
     */
    public function result(): string|array
    {
        return $this->result;
    }

    /**
     * Get the result as a string (throws if array subject was used).
     */
    public function toString(): string
    {
        if (is_array($this->result)) {
            return implode('', $this->result);
        }

        return $this->result;
    }

    /**
     * Get the number of replacements made.
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Check if any replacements were made.
     */
    public function hasReplacements(): bool
    {
        return $this->count > 0;
    }

    /**
     * Check if no replacements were made.
     */
    public function unchanged(): bool
    {
        return $this->count === 0;
    }

    /**
     * Get the original pattern(s).
     *
     * @return array<string>|string
     */
    public function pattern(): string|array
    {
        return $this->pattern;
    }

    /**
     * Get the original subject(s).
     *
     * @return array<string>|string
     */
    public function subject(): string|array
    {
        return $this->subject;
    }

    /**
     * Get the replacement value.
     */
    public function replacement(): mixed
    {
        return $this->replacement;
    }

    /**
     * Check if the result equals the original subject (no changes).
     */
    public function equals(string $expected): bool
    {
        return $this->result === $expected;
    }

    /**
     * Chain another replacement operation.
     *
     * @param array<Pattern|string>|Pattern|string $pattern
     * @param array<string>|callable|string        $replacement
     */
    public function then(string|Pattern|array $pattern, string|array|callable $replacement, int $limit = -1): self
    {
        return self::for($pattern, $replacement, $this->result, $limit);
    }

    /**
     * Perform replacement with a callback that receives a Match object.
     *
     * @param array<string>|string $patterns
     * @param array<string>|string $subject
     *
     * @return null|array<string>|string
     */
    private static function replaceWithCallback(
        string|array $patterns,
        callable $callback,
        string|array $subject,
        int $limit,
        int &$count,
    ): string|array|null {
        // Wrap the callback to provide a Match object
        $wrapper = function (array $matches) use ($patterns, $subject, $callback): string {
            $patternString = is_array($patterns) ? $patterns[0] : $patterns;
            $subjectString = is_array($subject) ? $subject[0] : $subject;

            /** @var array<int|string, null|string> $matches */
            $matchResult = new MatchResult($patternString, $subjectString, true, $matches);

            /** @var bool|float|int|string|Stringable $result */
            $result = $callback($matchResult);

            return is_string($result) ? $result : (string) $result;
        };

        return preg_replace_callback($patterns, $wrapper, $subject, $limit, $count, PREG_UNMATCHED_AS_NULL);
    }

    /**
     * Normalize patterns to string format.
     *
     * @param array<Pattern|string>|Pattern|string $pattern
     *
     * @return array<string>|string
     */
    private static function normalizePatterns(string|Pattern|array $pattern): string|array
    {
        if ($pattern instanceof Pattern) {
            return $pattern->toString();
        }

        if (is_array($pattern)) {
            return array_map(
                fn (string|Pattern $p): string => $p instanceof Pattern ? $p->toString() : $p,
                $pattern,
            );
        }

        return $pattern;
    }
}
