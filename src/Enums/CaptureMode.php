<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\Enums;

/**
 * Capture modes for controlling what is returned from regex matches.
 *
 * Inspired by Elixir's Regex module, these modes control which parts
 * of a match are captured and returned.
 * @author Brian Faust <brian@cline.sh>
 */
enum CaptureMode: string
{
    /**
     * Capture all groups including the full match (default).
     *
     * Returns the complete match at index 0, followed by all captured groups.
     */
    case All = 'all';

    /**
     * Capture only the first group.
     *
     * Returns only the first captured group, excluding the full match.
     */
    case First = 'first';

    /**
     * Capture all groups except the full match.
     *
     * Returns all captured groups but excludes the complete match (index 0).
     */
    case AllButFirst = 'all_but_first';

    /**
     * Capture only named groups.
     *
     * Returns only groups that have explicit names (e.g., (?<name>...)).
     */
    case Named = 'named';

    /**
     * Don't capture anything, just test for match.
     *
     * Returns only a boolean indicating whether the pattern matched.
     */
    case None = 'none';

    /**
     * Get a human-readable description of the capture mode.
     */
    public function description(): string
    {
        return match ($this) {
            self::All => 'Capture full match and all groups',
            self::First => 'Capture only the first group',
            self::AllButFirst => 'Capture all groups except full match',
            self::Named => 'Capture only named groups',
            self::None => 'No capture, boolean match only',
        };
    }
}
