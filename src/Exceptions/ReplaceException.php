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
 * Exception thrown when a regex replace operation fails.
 * @author Brian Faust <brian@cline.sh>
 */
final class ReplaceException extends RelexException
{
    /**
     * Create exception for a failed replace operation.
     */
    public static function failed(string $pattern, string $subject, string $error): self
    {
        return new self(
            sprintf(
                'Error replacing pattern "%s" in subject "%s": %s',
                self::truncate($pattern),
                self::truncate($subject),
                $error,
            ),
        );
    }

    /**
     * Create exception for invalid replacement callback.
     */
    public static function invalidCallback(string $pattern): self
    {
        return new self(
            sprintf('Invalid replacement callback provided for pattern "%s"', self::truncate($pattern)),
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
