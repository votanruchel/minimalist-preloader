<?php

declare(strict_types=1);

namespace MinimalistLoader\Enum;

defined('ABSPATH') || exit;

enum TimeUnit: string
{
    case Seconds = 's';
    case Milliseconds = 'ms';

    public static function coerce(mixed $value): self
    {
        return self::tryFrom(strtolower(trim((string) $value))) ?? self::Seconds;
    }

    public function label(): string
    {
        return match ($this) {
            self::Seconds => __('Seconds', 'minimalist-loader'),
            self::Milliseconds => __('Milliseconds', 'minimalist-loader'),
        };
    }

    /** Length of one of this unit, in milliseconds. */
    public function inMilliseconds(): int
    {
        return match ($this) {
            self::Seconds => 1000,
            self::Milliseconds => 1,
        };
    }
}
