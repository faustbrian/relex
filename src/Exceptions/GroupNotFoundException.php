<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\Exceptions;

use function mb_strlen;
use function mb_substr;
use function sprintf;

/**
 * Exception thrown when a requested capture group does not exist.
 * @author Brian Faust <brian@cline.sh>
 */
final class GroupNotFoundException extends RelexException
{
    /**
     * Create exception for a missing group by index.
     */
    public static function byIndex(int $index, string $pattern): self
    {
        return new self(
            sprintf('Capture group %d does not exist in pattern "%s"', $index, self::truncate($pattern)),
        );
    }

    /**
     * Create exception for a missing named group.
     */
    public static function byName(string $name, string $pattern): self
    {
        return new self(
            sprintf('Named capture group "%s" does not exist in pattern "%s"', $name, self::truncate($pattern)),
        );
    }

    /**
     * Truncate long strings for readable error messages.
     */
    private static function truncate(string $value, int $length = 50): string
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length).'...';
    }
}
