<?php

declare(strict_types=1);

namespace MinimalistLoader\Enum;

defined('ABSPATH') || exit;

enum Preset: string
{
    case Spinner = 'spinner';
    case Dots = 'dots';
    case Bar = 'bar';
    case DoubleRing = 'double-ring';
    case Pulse = 'pulse';

    public static function coerce(mixed $value): self
    {
        return self::tryFrom(strtolower(trim((string) $value))) ?? self::Spinner;
    }

    public function label(): string
    {
        return match ($this) {
            self::Spinner => __('Thin spinner', 'minimalist-loader'),
            self::Dots => __('Dots', 'minimalist-loader'),
            self::Bar => __('Bar', 'minimalist-loader'),
            self::DoubleRing => __('Double ring', 'minimalist-loader'),
            self::Pulse => __('Pulse', 'minimalist-loader'),
        };
    }
}
