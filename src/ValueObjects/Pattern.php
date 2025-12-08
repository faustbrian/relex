<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\ValueObjects;

use Cline\Relex\Enums\Modifier;
use Cline\Relex\Exceptions\PatternCompilationException;
use Stringable;

use function array_filter;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function mb_strlen;
use function mb_strrpos;
use function mb_substr;
use function preg_last_error_msg;
use function preg_match;
use function preg_match_all;
use function set_error_handler;

/**
 * A value object representing a compiled regex pattern with modifiers.
 *
 * This class provides a fluent API for building regex patterns with
 * type-safe modifier configuration and pattern validation.
 *
 * @example
 * ```php
 * $pattern = Pattern::create('\d+')
 *     ->caseInsensitive()
 *     ->multiline()
 *     ->utf8();
 *
 * // Or using shorthand methods
 * $pattern = Pattern::create('\d+')->i()->m()->u();
 *
 * // Or from a complete pattern string
 * $pattern = Pattern::from('/\d+/imu');
 * ```
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Pattern implements Stringable
{
    /**
     * @param string          $expression The regex expression without delimiters
     * @param string          $delimiter  The delimiter character (default: /)
     * @param array<Modifier> $modifiers  Array of pattern modifiers
     */
    private function __construct(
        private string $expression,
        private string $delimiter = '/',
        private array $modifiers = [],
    ) {}

    /**
     * Magic method for string conversion.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Create a new Pattern from a raw expression (without delimiters).
     *
     * @param string $expression The regex expression without delimiters
     * @param string $delimiter  The delimiter to use (default: /)
     */
    public static function create(string $expression, string $delimiter = '/'): self
    {
        return new self($expression, $delimiter);
    }

    /**
     * Create a Pattern from a complete pattern string with delimiters.
     *
     * @param string $pattern Complete pattern like "/\d+/im"
     */
    public static function from(string $pattern): self
    {
        if ($pattern === '' || mb_strlen($pattern) < 2) {
            throw PatternCompilationException::missingDelimiter($pattern);
        }

        $delimiter = $pattern[0];
        $lastDelimiterPos = mb_strrpos($pattern, $delimiter, 1);

        if ($lastDelimiterPos === false) {
            throw PatternCompilationException::missingDelimiter($pattern);
        }

        $expression = mb_substr($pattern, 1, $lastDelimiterPos - 1);
        $modifierString = mb_substr($pattern, $lastDelimiterPos + 1);
        $modifiers = Modifier::fromString($modifierString);

        return new self($expression, $delimiter, $modifiers);
    }

    /**
     * Validate that the pattern is syntactically correct.
     *
     * @throws PatternCompilationException If the pattern is invalid
     */
    public static function validate(string $pattern): void
    {
        $previousHandler = set_error_handler(static fn (): bool => true);

        try {
            $result = preg_match($pattern, '');

            if ($result === false) {
                throw PatternCompilationException::invalidSyntax($pattern, preg_last_error_msg());
            }
        } finally {
            set_error_handler($previousHandler);
        }
    }

    /**
     * Check if a pattern is syntactically valid.
     */
    public static function isValid(string $pattern): bool
    {
        try {
            self::validate($pattern);

            return true;
        } catch (PatternCompilationException) {
            return false;
        }
    }

    /**
     * Get the raw expression without delimiters or modifiers.
     */
    public function expression(): string
    {
        return $this->expression;
    }

    /**
     * Get the delimiter character.
     */
    public function delimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Get the array of modifiers.
     *
     * @return array<Modifier>
     */
    public function modifiers(): array
    {
        return $this->modifiers;
    }

    /**
     * Get the modifier string (e.g., "imu").
     */
    public function modifierString(): string
    {
        return Modifier::toString($this->modifiers);
    }

    /**
     * Check if a specific modifier is set.
     */
    public function hasModifier(Modifier $modifier): bool
    {
        return in_array($modifier, $this->modifiers, true);
    }

    /**
     * Get the complete pattern string ready for preg_* functions.
     */
    public function toString(): string
    {
        return $this->delimiter.$this->expression.$this->delimiter.$this->modifierString();
    }

    /**
     * Add a modifier to the pattern.
     */
    public function withModifier(Modifier $modifier): self
    {
        if ($this->hasModifier($modifier)) {
            return $this;
        }

        return new self($this->expression, $this->delimiter, [...$this->modifiers, $modifier]);
    }

    /**
     * Add multiple modifiers to the pattern.
     */
    public function withModifiers(Modifier ...$modifiers): self
    {
        $newModifiers = $this->modifiers;

        foreach ($modifiers as $modifier) {
            if (in_array($modifier, $newModifiers, true)) {
                continue;
            }

            $newModifiers[] = $modifier;
        }

        return new self($this->expression, $this->delimiter, $newModifiers);
    }

    /**
     * Remove a modifier from the pattern.
     */
    public function withoutModifier(Modifier $modifier): self
    {
        return new self(
            $this->expression,
            $this->delimiter,
            array_values(array_filter($this->modifiers, fn (Modifier $m): bool => $m !== $modifier)),
        );
    }

    // Fluent modifier methods (verbose)

    /**
     * Enable case-insensitive matching.
     */
    public function caseInsensitive(): self
    {
        return $this->withModifier(Modifier::CaseInsensitive);
    }

    /**
     * Enable multiline mode (^ and $ match line boundaries).
     */
    public function multiline(): self
    {
        return $this->withModifier(Modifier::Multiline);
    }

    /**
     * Enable single-line mode (dot matches newlines).
     */
    public function singleLine(): self
    {
        return $this->withModifier(Modifier::SingleLine);
    }

    /**
     * Enable extended/verbose mode (whitespace ignored, # comments).
     */
    public function extended(): self
    {
        return $this->withModifier(Modifier::Extended);
    }

    /**
     * Enable UTF-8 mode.
     */
    public function utf8(): self
    {
        return $this->withModifier(Modifier::Utf8);
    }

    /**
     * Enable anchored mode (pattern anchored at start).
     */
    public function anchored(): self
    {
        return $this->withModifier(Modifier::Anchored);
    }

    /**
     * Enable dollar end only mode ($ only matches end of string).
     */
    public function dollarEndOnly(): self
    {
        return $this->withModifier(Modifier::DollarEndOnly);
    }

    /**
     * Enable ungreedy mode (quantifiers are lazy by default).
     */
    public function ungreedy(): self
    {
        return $this->withModifier(Modifier::Ungreedy);
    }

    /**
     * Allow duplicate named capture groups.
     */
    public function duplicateNames(): self
    {
        return $this->withModifier(Modifier::DuplicateNames);
    }

    /**
     * Enable pattern study for optimization.
     */
    public function study(): self
    {
        return $this->withModifier(Modifier::Study);
    }

    // Shorthand modifier methods

    /**
     * Case-insensitive (shorthand for caseInsensitive).
     */
    public function i(): self
    {
        return $this->caseInsensitive();
    }

    /**
     * Multiline (shorthand for multiline).
     */
    public function m(): self
    {
        return $this->multiline();
    }

    /**
     * Single-line/dotall (shorthand for singleLine).
     */
    public function s(): self
    {
        return $this->singleLine();
    }

    /**
     * Extended/verbose (shorthand for extended).
     */
    public function x(): self
    {
        return $this->extended();
    }

    /**
     * UTF-8 (shorthand for utf8).
     */
    public function u(): self
    {
        return $this->utf8();
    }

    // Pattern introspection methods

    /**
     * Get the names of all named capture groups in the pattern.
     *
     * @return array<string>
     */
    public function groupNames(): array
    {
        preg_match_all("/\\(\\?(?:P?<|')([a-zA-Z_]\\w*)(?:>|')/", $this->expression, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Count the number of capture groups in the pattern.
     */
    public function groupCount(): int
    {
        // Match pattern to empty string to get group info
        $pattern = $this->toString();
        preg_match($pattern, '', $matches);

        return $matches !== [] ? count($matches) - 1 : 0;
    }

    /**
     * Check if the pattern has any named capture groups.
     */
    public function hasNamedGroups(): bool
    {
        return $this->groupNames() !== [];
    }
}
