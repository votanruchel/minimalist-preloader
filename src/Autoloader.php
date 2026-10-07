<?php

declare(strict_types=1);

namespace MinimalistLoader;

defined('ABSPATH') || exit;

/**
 * PSR-4 autoloader scoped to this plugin's namespace.
 *
 * Loading on demand keeps a frontend request from ever reading the admin classes.
 */
final class Autoloader
{
    public static function register(string $root): void
    {
        $prefix = __NAMESPACE__ . '\\';
        $offset = strlen($prefix);
        $root = rtrim($root, '/');

        spl_autoload_register(static function (string $class) use ($root, $prefix, $offset): void {
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $file = $root . '/' . str_replace('\\', '/', substr($class, $offset)) . '.php';

            if (is_file($file)) {
                require_once $file;
            }
        });
    }
}
