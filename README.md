# Minimalist Loader

Minimalist Loader is a WordPress plugin for publishers that run monetization through Google Ad Manager and need a controlled, lightweight loading layer before exposing the page experience.

## Core

- Google Ad Manager-first preloader for publisher sites
- Waits for the first configured ad block to become ready
- Holds through a rebid cascade instead of releasing on the no-fill that starts it
- Uses a maximum timeout to avoid blocking the page indefinitely
- Optionally keeps scrolling locked for a set time after the loader closes
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
- Color, blur intensity, timing, and fade controls
- Optional post-release scroll lock, set in seconds or milliseconds (up to 10 seconds)
- Optional subtitle
- Optional logo through the WordPress Media Library
- Google Ad Manager block list
- Display rules by content type
- Manual and searchable post/page exclusions

## Rebid Awareness

A block that returns no fill still emits `slotRenderEnded`. When a rebid wrapper reacts to that event by re-requesting the same div at descending prices, releasing on the first empty render would drop the loader exactly as the cascade begins, and the ad would land on an already-visible page.

If `window.SFM.Rebid` is present, the loader detects an active cascade for the div and holds until the wrapper emits `sfm:rebid_won` or `sfm:rebid_exhausted`. Detection is automatic; sites without that wrapper behave exactly as before.

Two things to know:

- **Maximum time still wins.** It is a hard ceiling, so a stuck cascade can never trap the visitor. Raise it (up to 30000 ms) when your blocks use rebid — the default 4000 ms can cut a long cascade short.
- **Only "when the ad finishes rendering" can see a cascade.** `slotOnload` and `impressionViewable` do not fire on a no-fill, so under those settings the loader falls back to the maximum time.

## Requirements

- WordPress 6.4+
- PHP 8.1+
- Google Ad Manager / Google Publisher Tag present on the frontend

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **Minimalist Loader** in WordPress.
3. Configure it under `Settings > Minimalist Loader`.

## Architecture

No build step and no runtime dependencies — the ZIP is the source.

```
minimalist-loader.php   Plugin header, autoloader registration, bootstrap
uninstall.php           Option cleanup, including the flat 1.x rows
src/
  Plugin.php            Wiring; frontend requests never load the admin classes
  Autoloader.php        PSR-4 autoloader scoped to the plugin namespace
  Enum/                 Preset, GamEvent, Location, TimeUnit
  Settings/             Readonly value objects; hydration is the sanitization
  Admin/                Settings screen and the REST search endpoint
  Frontend/             Enqueue, markup, and release logic
assets/                 Two stylesheets, two scripts, no framework
```

## Changelog

### 2.2.0

- Adjustable background blur intensity (0 to 20 px), keeping 6 px as the default

### 2.1.1

- Fixed the loader showing on a static front page with Home unchecked: the front page matched Pages too

### 2.1.0

- Optional scroll lock that keeps the page still for a configurable time after the loader is gone, set in seconds or milliseconds and capped at 10 seconds

### 2.0.0

- Preloader now holds while a rebid cascade re-requests a block, releasing on the cascade outcome instead of on the no-fill that started it
- Raised the floor to PHP 8.1 and WordPress 6.4
- Rewrote the plugin around a namespaced, autoloaded `src/` tree with typed readonly settings objects and enums
- Replaced the `admin-ajax.php` search endpoint with a REST route
- Dropped jQuery from the settings screen
- Frontend script rewritten in modern JavaScript, using a monotonic clock and a single teardown path
- Added `uninstall.php` so removal no longer leaves orphan options
- Stopped stat-ing the frontend script on every page view for cache busting
- Settings are now re-validated on read, not only on save
- Reduced-motion, reduced-transparency, and forced-colors handling in the loader styles
- Fixed the timing clamp: a negative value is pinned to the floor instead of being flipped positive

### 1.0.0

- Initial release
- Google Ad Manager-first preloader
- Minimal loader presets
- Optional subtitle and logo support
- Display rules and post/page exclusions
- Configurable timing, colors, blur, and fade controls
