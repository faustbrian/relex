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
 * Exception thrown when a regex match operation fails.
 * @author Brian Faust <brian@cline.sh>
 */
final class MatchException extends RelexException
{
    /**
     * Create exception for a failed match operation.
     */
    public static function matchFailed(string $pattern, string $subject, string $error): self
    {
        return new self(
            sprintf(
                'Error matching pattern "%s" against subject "%s": %s',
                self::truncate($pattern),
                self::truncate($subject),
                $error,
            ),
        );
    }

    /**
     * Create exception for a failed matchAll operation.
     */
    public static function matchAllFailed(string $pattern, string $subject, string $error): self
    {
        return new self(
            sprintf(
                'Error matching all occurrences of pattern "%s" in subject "%s": %s',
                self::truncate($pattern),
                self::truncate($subject),
                $error,
            ),
        );
    }

    /**
     * Truncate long strings for readable error messages.
     */
    private static function truncate(string $value, int $length = 40): string
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length).'...';
    }
}
