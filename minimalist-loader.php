<?php
/**
 * Plugin Name: Minimalist Loader
 * Plugin URI: https://votan.dev
 * Description: Minimal preloader integrated with native Google Ad Manager events.
 * Author: Votan Ruchel
 * Version: 1.0.0
 * Author URI: https://votan.dev
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MINIMALIST_LOADER_VERSION', '1.0.0');
define('MINIMALIST_LOADER_PLUGIN_FILE', __FILE__);
define('MINIMALIST_LOADER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MINIMALIST_LOADER_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once MINIMALIST_LOADER_PLUGIN_DIR . 'includes/class-minimalist-loader-sanitizer.php';
require_once MINIMALIST_LOADER_PLUGIN_DIR . 'includes/class-minimalist-loader.php';
require_once MINIMALIST_LOADER_PLUGIN_DIR . 'includes/class-minimalist-loader-admin.php';
require_once MINIMALIST_LOADER_PLUGIN_DIR . 'includes/class-minimalist-loader-frontend.php';

function minimalist_loader()
{
    static $plugin = null;

    if ($plugin === null) {
        $plugin = new Minimalist_Loader();
    }

    return $plugin;
}

add_action('plugins_loaded', 'minimalist_loader');
