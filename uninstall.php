<?php

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

/**
 * Removes the grouped option plus the flat 1.x options the migration reads from.
 *
 * Installs that never ran 2.x still have the legacy rows, so both sets are cleared.
 */
$options = [
    'minimalist_loader_settings',
    'minimalist_loader_spinner_color',
    'minimalist_loader_spinner_bg_color',
    'minimalist_loader_background_color',
    'minimalist_loader_use_blur',
    'minimalist_loader_min_time',
    'minimalist_loader_max_time',
    'minimalist_loader_gpt_event',
];

foreach ($options as $option) {
    delete_option($option);

    if (is_multisite()) {
        delete_site_option($option);
    }
}
