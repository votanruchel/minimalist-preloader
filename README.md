# Minimalist Loader

Minimalist Loader is a WordPress plugin for publishers that run monetization through Google Ad Manager and need a controlled, lightweight loading layer before exposing the page experience.

## Core

- Google Ad Manager-first preloader for publisher sites
- Waits for the first configured ad block to become ready
- Uses a maximum timeout to avoid blocking the page indefinitely
- Keeps the frontend small and dependency-free
- Provides visual presets without requiring theme changes
- Supports optional logo and subtitle for branded loading states
- Allows display targeting by page context
- Supports post/page exclusions by ID

## Who It Is For

Minimalist Loader is intended for publishers, media sites, and content portals that:

- Serve ads through Google Ad Manager
- Need better control over the first visible page state
- Want a minimal preloader without adding a heavy visual framework
- Need different behavior across home, posts, pages, and category archives
- Need simple editorial controls inside WordPress

## What It Does

The plugin displays a configurable preloader while the page and selected ad blocks initialize. Once one of the configured blocks is ready, the loader closes while respecting the minimum display time. If the ad stack takes too long, the maximum time setting releases the page.

The admin interface includes:

- Loader model selection
- Color, blur, timing, and fade controls
- Optional subtitle
- Optional logo through the WordPress Media Library
- Google Ad Manager block list
- Display rules by content type
- Manual and searchable post/page exclusions

## Requirements

- WordPress 5.0+
- PHP 7.0+
- Google Ad Manager / Google Publisher Tag present on the frontend

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **Minimalist Loader** in WordPress.
3. Configure it under `Settings > Minimalist Loader`.

## Changelog

### 1.0.0

- Initial release
- Google Ad Manager-first preloader
- Minimal loader presets
- Optional subtitle and logo support
- Display rules and post/page exclusions
- Configurable timing, colors, blur, and fade controls
