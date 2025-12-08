<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex\Support;

use function mb_strlen;
use function mb_substr;

/**
 * Represents a position (byte offset) in a string.
 *
 * This value object encapsulates the start offset and length of a match,
 * providing convenient methods to work with match positions.
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Position
{
    /**
     * Create a new Position instance.
     *
     * @param int $start  The byte offset where the match starts
     * @param int $length The length of the match in bytes
     */
    public function __construct(
        public int $start,
        public int $length,
    ) {}

    /**
     * Create a Position from PREG_OFFSET_CAPTURE result.
     *
     * @param array{0: string, 1: int} $capture
     */
    public static function fromOffsetCapture(array $capture): self
    {
        return new self($capture[1], mb_strlen($capture[0]));
    }

    /**
     * Get the start offset.
     */
    public function start(): int
    {
        return $this->start;
    }

    /**
     * Get the end offset (exclusive).
     */
    public function end(): int
    {
        return $this->start + $this->length;
    }

    /**
     * Get the length of the match.
     */
    public function length(): int
    {
        return $this->length;
    }

    /**
     * Check if the position is valid (non-negative start).
     */
    public function isValid(): bool
    {
        return $this->start >= 0;
    }

    /**
     * Check if this position contains another position.
     */
    public function contains(self $other): bool
    {
        return $this->start <= $other->start && $this->end() >= $other->end();
    }

    /**
     * Check if this position overlaps with another position.
     */
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end() && $this->end() > $other->start;
    }

    /**
     * Extract the substring at this position from the given string.
     */
    public function extract(string $subject): string
    {
        return mb_substr($subject, $this->start, $this->length);
    }
}
