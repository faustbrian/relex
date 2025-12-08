<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\Facades;

use Cline\Relex\Relex as RelexManager;
use Cline\Relex\ValueObjects\MatchAllResult;
use Cline\Relex\ValueObjects\MatchResult;
use Cline\Relex\ValueObjects\Pattern;
use Cline\Relex\ValueObjects\ReplaceResult;
use Cline\Relex\ValueObjects\SplitResult;
use Illuminate\Support\Facades\Facade;

/**
 * Laravel Facade for Relex regex operations.
 *
 * @method static Pattern            any(array<string> $strings, string $delimiter = '/')
 * @method static Pattern            compile(string $expression, string $delimiter = '/')
 * @method static int                count(string|Pattern $pattern, string $subject)
 * @method static string             escape(string $value, ?string $delimiter = '/')
 * @method static array<string>      extract(string|Pattern $pattern, string $subject)
 * @method static array<string>      filter(string|Pattern $pattern, array<string> $strings)
 * @method static string|null        first(string|Pattern $pattern, array<string> $strings)
 * @method static bool               isValid(string $pattern)
 * @method static MatchResult        match(string|Pattern $pattern, string $subject, int $offset = 0)
 * @method static MatchAllResult     matchAll(string|Pattern $pattern, string $subject, int $offset = 0)
 * @method static MatchResult        matchWithOffsets(string|Pattern $pattern, string $subject, int $offset = 0)
 * @method static Pattern            pattern(string $pattern)
 * @method static array<string|null> pluck(string|Pattern $pattern, string $subject, string $group)
 * @method static array<string>      reject(string|Pattern $pattern, array<string> $strings)
 * @method static ReplaceResult      replace(string|Pattern|array<string> $pattern, string|array<string>|callable $replacement, string|array<string> $subject, int $limit = -1)
 * @method static ReplaceResult      replaceFirst(string|Pattern $pattern, string|callable $replacement, string $subject)
 * @method static SplitResult        split(string|Pattern $pattern, string $subject, int $limit = -1)
 * @method static SplitResult        splitWithDelimiters(string|Pattern $pattern, string $subject, int $limit = -1)
 * @method static bool               test(string|Pattern $pattern, string $subject, int $offset = 0)
 * @method static void               validate(string $pattern)
 *
 * @author Brian Faust <brian@cline.sh>
 * @see RelexManager
 */
final class Relex extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return RelexManager::class;
    }
}
