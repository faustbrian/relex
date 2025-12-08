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
 * Exception thrown when a regex pattern fails to compile.
 * @author Brian Faust <brian@cline.sh>
 */
final class PatternCompilationException extends RelexException
{
    /**
     * Create exception for invalid pattern syntax.
     */
    public static function invalidSyntax(string $pattern, string $error): self
    {
        return new self(
            sprintf('Invalid regex pattern "%s": %s', self::truncate($pattern), $error),
        );
    }

    /**
     * Create exception for missing delimiter.
     */
    public static function missingDelimiter(string $pattern): self
    {
        return new self(
            sprintf('Pattern "%s" is missing delimiters. Expected format: /pattern/modifiers', self::truncate($pattern)),
        );
    }

    /**
     * Create exception for invalid modifier.
     */
    public static function invalidModifier(string $modifier): self
    {
        return new self(
            sprintf('Invalid pattern modifier "%s". Valid modifiers are: i, m, s, x, u, A, D, U, J, S', $modifier),
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
