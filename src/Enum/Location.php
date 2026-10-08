<?php

declare(strict_types=1);

namespace MinimalistLoader\Enum;

defined('ABSPATH') || exit;

enum Location: string
{
    case Home = 'home';
    case Posts = 'posts';
    case Pages = 'pages';
    case Categories = 'categories';

    public static function tryCoerce(mixed $value): ?self
    {
        return self::tryFrom(sanitize_key((string) $value));
    }

    public function label(): string
    {
        return match ($this) {
            self::Home => __('Home', 'minimalist-loader'),
            self::Posts => __('Posts', 'minimalist-loader'),
            self::Pages => __('Pages', 'minimalist-loader'),
            self::Categories => __('Categories', 'minimalist-loader'),
        };
    }

    public function matchesCurrentRequest(): bool
    {
        return match ($this) {
            self::Home => is_front_page() || is_home(),
            self::Posts => is_singular('post'),
            // A static front page is also a page; it belongs to Home only, or unchecking
            // Home would not keep the loader off it while Pages is checked.
            self::Pages => is_page() && !is_front_page(),
            self::Categories => is_category(),
        };
    }
}
