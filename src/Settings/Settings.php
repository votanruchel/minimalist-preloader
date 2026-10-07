<?php

declare(strict_types=1);

namespace MinimalistLoader\Settings;

defined('ABSPATH') || exit;

/**
 * The full settings tree.
 *
 * Hydration is the sanitization: every value goes through the same coercion whether
 * it arrives from the settings form or straight out of the options table, so a value
 * that was tampered with in the database is corrected on read too.
 */
final class Settings
{
    public function __construct(
        public readonly Appearance $appearance = new Appearance(),
        public readonly Gam $gam = new Gam(),
        public readonly Display $display = new Display(),
    ) {
    }

    public static function fromArray(mixed $raw): self
    {
        $raw = is_array($raw) ? $raw : [];

        return new self(
            appearance: Appearance::fromArray(self::section($raw, 'appearance')),
            gam: Gam::fromArray(self::section($raw, 'gam')),
            display: Display::fromArray(self::section($raw, 'display')),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'appearance' => $this->appearance->toArray(),
            'gam' => $this->gam->toArray(),
            'display' => $this->display->toArray(),
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private static function section(array $raw, string $key): array
    {
        $section = $raw[$key] ?? [];

        return is_array($section) ? $section : [];
    }
}
