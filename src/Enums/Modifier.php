<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\Enums;

use function array_map;
use function implode;
use function mb_str_split;

/**
 * PCRE pattern modifiers for regex operations.
 *
 * These modifiers control how the regex engine interprets and matches patterns.
 * They correspond to the standard PCRE modifier letters used in PHP's preg_* functions.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php
 */
enum Modifier: string
{
    /**
     * Case-insensitive matching.
     *
     * Letters in the pattern match both upper and lower case letters.
     */
    case CaseInsensitive = 'i';

    /**
     * Multiline mode.
     *
     * ^ and $ match the start and end of each line, not just the string.
     */
    case Multiline = 'm';

    /**
     * Single-line (dotall) mode.
     *
     * The dot metacharacter (.) matches all characters including newlines.
     */
    case SingleLine = 's';

    /**
     * Extended (verbose) mode.
     *
     * Whitespace in the pattern is ignored (except in character classes),
     * and # starts a comment extending to the end of the line.
     */
    case Extended = 'x';

    /**
     * UTF-8 mode.
     *
     * Pattern and subject strings are treated as UTF-8.
     */
    case Utf8 = 'u';

    /**
     * Anchored mode.
     *
     * The pattern is forced to be anchored at the start of the subject.
     */
    case Anchored = 'A';

    /**
     * Dollar end only.
     *
     * $ matches only at the end of the string, not before newlines.
     */
    case DollarEndOnly = 'D';

    /**
     * Ungreedy mode.
     *
     * Quantifiers are ungreedy by default; adding ? makes them greedy.
     */
    case Ungreedy = 'U';

    /**
     * Allow duplicate named groups.
     *
     * Permits multiple groups with the same name in the pattern.
     */
    case DuplicateNames = 'J';

    /**
     * Study the pattern for optimization.
     *
     * Extra time spent analyzing the pattern for faster matching.
     */
    case Study = 'S';

    /**
     * Convert an array of modifiers to a modifier string.
     *
     * @param array<Modifier> $modifiers
     */
    public static function toString(array $modifiers): string
    {
        return implode('', array_map(fn (Modifier $m): string => $m->value, $modifiers));
    }

    /**
     * Parse a modifier string into an array of Modifier enums.
     *
     * @return array<Modifier>
     */
    public static function fromString(string $modifiers): array
    {
        $result = [];

        foreach (mb_str_split($modifiers) as $char) {
            $modifier = self::tryFrom($char);

            if ($modifier === null) {
                continue;
            }

            $result[] = $modifier;
        }

        return $result;
    }

    /**
     * Get the modifier character.
     */
    public function char(): string
    {
        return $this->value;
    }

    /**
     * Get a human-readable description of the modifier.
     */
    public function description(): string
    {
        return match ($this) {
            self::CaseInsensitive => 'Case-insensitive matching',
            self::Multiline => 'Multiline mode (^ and $ match line boundaries)',
            self::SingleLine => 'Single-line mode (dot matches newlines)',
            self::Extended => 'Extended mode (whitespace ignored, # comments)',
            self::Utf8 => 'UTF-8 mode',
            self::Anchored => 'Anchored at start of subject',
            self::DollarEndOnly => 'Dollar matches only at end of string',
            self::Ungreedy => 'Ungreedy quantifiers by default',
            self::DuplicateNames => 'Allow duplicate named groups',
            self::Study => 'Study pattern for optimization',
        };
    }
}
