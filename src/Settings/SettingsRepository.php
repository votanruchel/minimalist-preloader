<?php

declare(strict_types=1);

namespace MinimalistLoader\Settings;

use MinimalistLoader\Plugin;

defined('ABSPATH') || exit;

final class SettingsRepository
{
    /** Flat options written by 1.x, read once during the migration to the grouped option. */
    private const LEGACY_OPTIONS = [
        'minimalist_loader_spinner_color' => ['appearance', 'primary_color'],
        'minimalist_loader_spinner_bg_color' => ['appearance', 'secondary_color'],
        'minimalist_loader_background_color' => ['appearance', 'background_color'],
        'minimalist_loader_use_blur' => ['appearance', 'use_blur'],
        'minimalist_loader_min_time' => ['appearance', 'min_time'],
        'minimalist_loader_max_time' => ['appearance', 'max_time'],
        'minimalist_loader_gpt_event' => ['gam', 'event'],
    ];

    private ?Settings $cache = null;

    public function get(): Settings
    {
        return $this->cache ??= $this->load();
    }

    /**
     * register_setting() sanitize callback: hydrating and dumping back out is the sanitization.
     *
     * @return array<string, mixed>
     */
    public function sanitize(mixed $input): array
    {
        $this->cache = null;

        $clean = Settings::fromArray($input)->toArray();

        // Only the write path pays for reading the logo off disk to verify it.
        $clean['appearance']['logo_id'] = LogoAttachment::coerce($clean['appearance']['logo_id']);

        return $clean;
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return (new Settings())->toArray();
    }

    private function load(): Settings
    {
        $stored = get_option(Plugin::OPTION, null);

        if (is_array($stored)) {
            return Settings::fromArray($stored);
        }

        $migrated = Settings::fromArray($this->legacy());

        // add_option() is a no-op when the row already exists, so a concurrent request
        // racing this same migration cannot clobber the winner.
        add_option(Plugin::OPTION, $migrated->toArray());

        return $migrated;
    }

    /** @return array<string, mixed> */
    private function legacy(): array
    {
        $raw = [];

        foreach (self::LEGACY_OPTIONS as $option => [$section, $key]) {
            $value = get_option($option, null);

            if ($value !== null && $value !== false) {
                $raw[$section][$key] = $value;
            }
        }

        return $raw;
    }
}
