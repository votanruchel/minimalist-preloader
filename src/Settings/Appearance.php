<?php

declare(strict_types=1);

namespace MinimalistLoader\Settings;

use MinimalistLoader\Enum\Preset;
use MinimalistLoader\Enum\TimeUnit;

defined('ABSPATH') || exit;

final class Appearance
{
    public const DEFAULT_PRIMARY = '#111827';
    public const DEFAULT_SECONDARY = '#e5e7eb';
    public const DEFAULT_BACKGROUND = 'rgba(255,255,255,0.94)';

    public const SUBTITLE_MAX_LENGTH = 120;
    public const MIN_TIME_CEILING = 20000;
    public const MAX_TIME_FLOOR = 200;
    public const MAX_TIME_CEILING = 30000;
    public const FADE_CEILING = 3000;
    public const BLUR_CEILING = 20;
    public const SCROLL_LOCK_CEILING = 10000;

    public function __construct(
        public readonly Preset $preset = Preset::Spinner,
        public readonly string $primaryColor = self::DEFAULT_PRIMARY,
        public readonly string $secondaryColor = self::DEFAULT_SECONDARY,
        public readonly string $backgroundColor = self::DEFAULT_BACKGROUND,
        public readonly bool $useBlur = true,
        public readonly int $blurRadius = 6,
        public readonly int $logoId = 0,
        public readonly string $subtitle = '',
        public readonly int $minTime = 400,
        public readonly int $maxTime = 4000,
        public readonly int $fadeDuration = 240,
        public readonly bool $scrollLock = false,
        public readonly int $scrollLockDuration = 1,
        public readonly TimeUnit $scrollLockUnit = TimeUnit::Seconds,
    ) {
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $scrollLockUnit = TimeUnit::coerce($raw['scroll_lock_unit'] ?? null);

        return new self(
            preset: Preset::coerce($raw['preset'] ?? null),
            primaryColor: self::color($raw['primary_color'] ?? null, self::DEFAULT_PRIMARY),
            secondaryColor: self::color($raw['secondary_color'] ?? null, self::DEFAULT_SECONDARY),
            backgroundColor: self::color($raw['background_color'] ?? null, self::DEFAULT_BACKGROUND),
            useBlur: !empty($raw['use_blur']),
            blurRadius: self::clamp($raw['blur_radius'] ?? 6, 0, self::BLUR_CEILING),
            // Cheap on purpose: hydration runs on every read, and the deep file check
            // lives in the save path (SettingsRepository::sanitize).
            logoId: absint($raw['logo_id'] ?? 0),
            subtitle: self::text($raw['subtitle'] ?? '', self::SUBTITLE_MAX_LENGTH),
            minTime: self::clamp($raw['min_time'] ?? 400, 0, self::MIN_TIME_CEILING),
            maxTime: self::clamp($raw['max_time'] ?? 4000, self::MAX_TIME_FLOOR, self::MAX_TIME_CEILING),
            fadeDuration: self::clamp($raw['fade_duration'] ?? 240, 0, self::FADE_CEILING),
            scrollLock: !empty($raw['scroll_lock']),
            // The ceiling is in milliseconds, so it is converted into whichever unit was picked.
            scrollLockDuration: self::clamp(
                $raw['scroll_lock_duration'] ?? 1,
                0,
                intdiv(self::SCROLL_LOCK_CEILING, $scrollLockUnit->inMilliseconds())
            ),
            scrollLockUnit: $scrollLockUnit,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'preset' => $this->preset->value,
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'background_color' => $this->backgroundColor,
            'use_blur' => $this->useBlur ? '1' : '0',
            'blur_radius' => $this->blurRadius,
            'logo_id' => $this->logoId,
            'subtitle' => $this->subtitle,
            'min_time' => $this->minTime,
            'max_time' => $this->maxTime,
            'fade_duration' => $this->fadeDuration,
            'scroll_lock' => $this->scrollLock ? '1' : '0',
            'scroll_lock_duration' => $this->scrollLockDuration,
            'scroll_lock_unit' => $this->scrollLockUnit->value,
        ];
    }

    /** How long scrolling stays locked once the loader is gone, or 0 when the option is off. */
    public function scrollLockMilliseconds(): int
    {
        return $this->scrollLock ? $this->scrollLockDuration * $this->scrollLockUnit->inMilliseconds() : 0;
    }

    /**
     * Accepts a hex color or an rgb()/rgba() literal, falling back to the default.
     *
     * The result is interpolated straight into a <style> block, so the allowlist here
     * is also what keeps the value from breaking out of the CSS context.
     */
    private static function color(mixed $value, string $default): string
    {
        $value = trim((string) $value);
        $hex = sanitize_hex_color($value);

        if (is_string($hex) && $hex !== '') {
            return $hex;
        }

        $pattern = '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0|1|0?\.\d+))?\s*\)$/';

        if (preg_match($pattern, $value, $matches) !== 1) {
            return $default;
        }

        [$red, $green, $blue] = [(int) $matches[1], (int) $matches[2], (int) $matches[3]];

        return $red <= 255 && $green <= 255 && $blue <= 255 ? $value : $default;
    }

    private static function text(mixed $value, int $limit): string
    {
        return mb_substr(sanitize_text_field((string) $value), 0, $limit);
    }

    /**
     * A real clamp. 1.x ran absint() first, which turned a negative into its positive
     * twin (-50 became 50) instead of pinning it to the floor.
     */
    private static function clamp(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }
}
