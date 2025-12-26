<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\Enums;

use const PREG_OFFSET_CAPTURE;
use const PREG_PATTERN_ORDER;
use const PREG_SET_ORDER;
use const PREG_UNMATCHED_AS_NULL;

use function array_reduce;

/**
 * PCRE match flags for preg_match and preg_match_all operations.
 *
 * These flags control how matches are captured and returned.
 * @author Brian Faust <brian@cline.sh>
 */
enum MatchFlag: int
{
    /**
     * Capture byte offsets along with matches.
     *
     * Each match becomes an array with the matched string and its byte offset.
     */
    case OffsetCapture = PREG_OFFSET_CAPTURE;

    /**
     * Report unmatched groups as null instead of empty string.
     *
     * Groups that didn't participate in the match will be null, not "".
     */
    case UnmatchedAsNull = PREG_UNMATCHED_AS_NULL;

    /**
     * Order results by pattern (default for preg_match_all).
     *
     * Results are grouped by capture group number.
     */
    case PatternOrder = PREG_PATTERN_ORDER;

    /**
     * Order results by set.
     *
     * Results are grouped by match occurrence.
     */
    case SetOrder = PREG_SET_ORDER;

    /**
     * Combine multiple flags into a single integer.
     *
     * @param array<MatchFlag> $flags
     */
    public static function combine(array $flags): int
    {
        return array_reduce(
            $flags,
            fn (int $carry, MatchFlag $flag): int => $carry | $flag->value,
            0,
        );
    }

    /**
     * Get a human-readable description of the flag.
     */
    public function description(): string
    {
        return match ($this) {
            self::OffsetCapture => 'Include byte offsets with matches',
            self::UnmatchedAsNull => 'Unmatched groups return null',
            self::PatternOrder => 'Group results by capture group',
            self::SetOrder => 'Group results by match occurrence',
        };
    }
}
