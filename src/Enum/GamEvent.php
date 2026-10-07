<?php

declare(strict_types=1);

namespace MinimalistLoader\Enum;

defined('ABSPATH') || exit;

/**
 * GPT pubads() events the preloader is allowed to wait for.
 *
 * Only slotRenderEnded fires on a no-fill, so it is the only value that can
 * observe (and hold for) a rebid cascade.
 */
enum GamEvent: string
{
    case SlotRenderEnded = 'slotRenderEnded';
    case SlotOnload = 'slotOnload';
    case ImpressionViewable = 'impressionViewable';

    public static function coerce(mixed $value): self
    {
        $normalized = strtolower((string) preg_replace('/[^A-Za-z]/', '', (string) $value));

        return match ($normalized) {
            'slotonload' => self::SlotOnload,
            'impressionviewable' => self::ImpressionViewable,
            default => self::SlotRenderEnded,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SlotRenderEnded => __('When the ad finishes rendering', 'minimalist-loader'),
            self::SlotOnload => __('When the ad fully loads', 'minimalist-loader'),
            self::ImpressionViewable => __('When the ad becomes viewable', 'minimalist-loader'),
        };
    }

    /** Whether this event is emitted for unfilled slots, and can therefore see a rebid cascade. */
    public function firesOnNoFill(): bool
    {
        return $this === self::SlotRenderEnded;
    }
}
