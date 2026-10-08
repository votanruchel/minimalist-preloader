<?php
/**
 * Plugin Name:       Minimalist Loader
 * Plugin URI:        https://votan.dev
 * Description:       Minimal preloader integrated with native Google Ad Manager events.
 * Version:           2.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Votan Ruchel
 * Author URI:        https://votan.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       minimalist-loader
 * Domain Path:       /languages
 */

declare(strict_types=1);

namespace MinimalistLoader;

defined('ABSPATH') || exit;

const VERSION = '2.2.0';

require_once __DIR__ . '/src/Autoloader.php';

Autoloader::register(__DIR__ . '/src');

add_action('plugins_loaded', static function (): void {
    (new Plugin(__FILE__, VERSION))->register();
});
