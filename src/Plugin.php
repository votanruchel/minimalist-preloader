<?php

declare(strict_types=1);

namespace MinimalistLoader;

use MinimalistLoader\Admin\SearchController;
use MinimalistLoader\Admin\SettingsPage;
use MinimalistLoader\Frontend\Preloader;
use MinimalistLoader\Settings\SettingsRepository;

defined('ABSPATH') || exit;

final class Plugin
{
    public const OPTION = 'minimalist_loader_settings';
    public const SETTINGS_GROUP = 'minimalist_loader_settings_group';
    public const MENU_SLUG = 'minimalist-loader';
    public const REST_NAMESPACE = 'minimalist-loader/v1';

    public readonly SettingsRepository $settings;

    public function __construct(
        private readonly string $file,
        public readonly string $version,
    ) {
        $this->settings = new SettingsRepository();
    }

    public function register(): void
    {
        add_action('init', $this->loadTranslations(...));

        // The REST controller hooks rest_api_init, which runs outside the admin context.
        (new SearchController($this))->register();

        if (is_admin()) {
            (new SettingsPage($this))->register();

            return;
        }

        (new Preloader($this))->register();
    }

    public function url(string $relative): string
    {
        return plugins_url($relative, $this->file);
    }

    public function path(string $relative = ''): string
    {
        return plugin_dir_path($this->file) . ltrim($relative, '/');
    }

    public function loadTranslations(): void
    {
        load_plugin_textdomain('minimalist-loader', false, dirname(plugin_basename($this->file)) . '/languages');
    }
}
