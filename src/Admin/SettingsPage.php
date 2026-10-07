<?php

declare(strict_types=1);

namespace MinimalistLoader\Admin;

use MinimalistLoader\Enum\GamEvent;
use MinimalistLoader\Enum\Location;
use MinimalistLoader\Enum\Preset;
use MinimalistLoader\Enum\TimeUnit;
use MinimalistLoader\Plugin;
use MinimalistLoader\Settings\Appearance;

defined('ABSPATH') || exit;

final class SettingsPage
{
    private string $pageHook = '';

    public function __construct(private readonly Plugin $plugin)
    {
    }

    public function register(): void
    {
        add_action('admin_init', $this->registerSetting(...));
        add_action('admin_menu', $this->addMenu(...));
        add_action('admin_enqueue_scripts', $this->enqueueAssets(...));
    }

    public function registerSetting(): void
    {
        register_setting(Plugin::SETTINGS_GROUP, Plugin::OPTION, [
            'type' => 'array',
            'sanitize_callback' => $this->plugin->settings->sanitize(...),
            'default' => $this->plugin->settings->defaults(),
        ]);
    }

    public function addMenu(): void
    {
        $this->pageHook = (string) add_options_page(
            __('Minimalist Loader', 'minimalist-loader'),
            __('Minimalist Loader', 'minimalist-loader'),
            'manage_options',
            Plugin::MENU_SLUG,
            $this->render(...)
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== $this->pageHook || $this->pageHook === '') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'minimalist-loader-admin',
            $this->plugin->url('assets/admin.css'),
            [],
            $this->plugin->version
        );

        wp_enqueue_script(
            'minimalist-loader-admin',
            $this->plugin->url('assets/admin.js'),
            [],
            $this->plugin->version,
            ['in_footer' => true, 'strategy' => 'defer']
        );

        wp_add_inline_script(
            'minimalist-loader-admin',
            sprintf('window.MinimalistLoaderAdmin=%s;', wp_json_encode([
                'searchUrl' => SearchController::url(),
                'nonce' => wp_create_nonce('wp_rest'),
                'optionName' => Plugin::OPTION,
                'i18n' => [
                    'mediaTitle' => __('Select logo', 'minimalist-loader'),
                    'mediaButton' => __('Use this logo', 'minimalist-loader'),
                    'searching' => __('Searching...', 'minimalist-loader'),
                    'noResults' => __('No content found.', 'minimalist-loader'),
                    'manualId' => __('Manual ID', 'minimalist-loader'),
                    'remove' => __('Remove', 'minimalist-loader'),
                ],
            ]) ?: '{}'),
            'before'
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->plugin->settings->get();
        $appearance = $settings->appearance;
        $gam = $settings->gam;
        $display = $settings->display;
        $option = Plugin::OPTION;
        $logoUrl = $appearance->logoId > 0
            ? (string) wp_get_attachment_image_url($appearance->logoId, 'medium')
            : '';
        ?>
        <div class="wrap minimalist-loader-admin">
            <h1><?php esc_html_e('Minimalist Loader', 'minimalist-loader'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields(Plugin::SETTINGS_GROUP); ?>

                <div class="ml-admin-grid">
                    <nav class="ml-admin-nav" aria-label="<?php esc_attr_e('Plugin sections', 'minimalist-loader'); ?>">
                        <a href="#ml-appearance"><?php esc_html_e('Appearance', 'minimalist-loader'); ?></a>
                        <a href="#ml-gam"><?php esc_html_e('Google Ad Manager', 'minimalist-loader'); ?></a>
                        <a href="#ml-display"><?php esc_html_e('Display', 'minimalist-loader'); ?></a>
                        <a href="#ml-exclusions"><?php esc_html_e('Exclusions', 'minimalist-loader'); ?></a>
                    </nav>

                    <main class="ml-admin-main">
                        <section class="ml-panel" id="ml-appearance">
                            <div class="ml-panel__header">
                                <h2><?php esc_html_e('Appearance', 'minimalist-loader'); ?></h2>
                                <p><?php esc_html_e('Minimal presets, optional branding, and concise loading copy.', 'minimalist-loader'); ?></p>
                            </div>

                            <div class="ml-field">
                                <span class="ml-field__label"><?php esc_html_e('Loader style', 'minimalist-loader'); ?></span>
                                <div class="ml-preset-grid">
                                    <?php foreach (Preset::cases() as $preset) : ?>
                                        <label class="ml-preset-option">
                                            <input type="radio" name="<?php echo esc_attr($option); ?>[appearance][preset]" value="<?php echo esc_attr($preset->value); ?>" <?php checked($appearance->preset->value, $preset->value); ?>>
                                            <span class="ml-preset-preview ml-preset-preview--<?php echo esc_attr($preset->value); ?>" aria-hidden="true"><i></i><i></i><i></i></span>
                                            <strong><?php echo esc_html($preset->label()); ?></strong>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="ml-columns">
                                <div class="ml-field">
                                    <label for="ml-primary-color"><?php esc_html_e('Primary color', 'minimalist-loader'); ?></label>
                                    <input id="ml-primary-color" type="text" name="<?php echo esc_attr($option); ?>[appearance][primary_color]" value="<?php echo esc_attr($appearance->primaryColor); ?>" placeholder="<?php echo esc_attr(Appearance::DEFAULT_PRIMARY); ?>">
                                </div>
                                <div class="ml-field">
                                    <label for="ml-secondary-color"><?php esc_html_e('Secondary color', 'minimalist-loader'); ?></label>
                                    <input id="ml-secondary-color" type="text" name="<?php echo esc_attr($option); ?>[appearance][secondary_color]" value="<?php echo esc_attr($appearance->secondaryColor); ?>" placeholder="<?php echo esc_attr(Appearance::DEFAULT_SECONDARY); ?>">
                                </div>
                                <div class="ml-field">
                                    <label for="ml-background-color"><?php esc_html_e('Screen background', 'minimalist-loader'); ?></label>
                                    <input id="ml-background-color" type="text" name="<?php echo esc_attr($option); ?>[appearance][background_color]" value="<?php echo esc_attr($appearance->backgroundColor); ?>" placeholder="<?php echo esc_attr(Appearance::DEFAULT_BACKGROUND); ?>">
                                </div>
                            </div>

                            <div class="ml-columns">
                                <div class="ml-field">
                                    <label for="ml-min-time"><?php esc_html_e('Minimum time (ms)', 'minimalist-loader'); ?></label>
                                    <input id="ml-min-time" type="number" min="0" max="<?php echo esc_attr((string) Appearance::MIN_TIME_CEILING); ?>" name="<?php echo esc_attr($option); ?>[appearance][min_time]" value="<?php echo esc_attr((string) $appearance->minTime); ?>">
                                </div>
                                <div class="ml-field">
                                    <label for="ml-max-time"><?php esc_html_e('Maximum time (ms)', 'minimalist-loader'); ?></label>
                                    <input id="ml-max-time" type="number" min="<?php echo esc_attr((string) Appearance::MAX_TIME_FLOOR); ?>" max="<?php echo esc_attr((string) Appearance::MAX_TIME_CEILING); ?>" name="<?php echo esc_attr($option); ?>[appearance][max_time]" value="<?php echo esc_attr((string) $appearance->maxTime); ?>">
                                    <p class="description"><?php esc_html_e('Hard ceiling. Raise it if your ad blocks use rebid, so the preloader can wait out the cascade.', 'minimalist-loader'); ?></p>
                                </div>
                                <div class="ml-field">
                                    <label for="ml-fade-duration"><?php esc_html_e('Fade out (ms)', 'minimalist-loader'); ?></label>
                                    <input id="ml-fade-duration" type="number" min="0" max="<?php echo esc_attr((string) Appearance::FADE_CEILING); ?>" name="<?php echo esc_attr($option); ?>[appearance][fade_duration]" value="<?php echo esc_attr((string) $appearance->fadeDuration); ?>">
                                </div>
                            </div>

                            <div class="ml-field ml-toggle-row">
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr($option); ?>[appearance][scroll_lock]" value="1" <?php checked($appearance->scrollLock); ?>>
                                    <?php esc_html_e('Keep scrolling locked after the loader closes', 'minimalist-loader'); ?>
                                </label>
                            </div>

                            <div class="ml-columns">
                                <div class="ml-field">
                                    <label for="ml-scroll-lock-duration"><?php esc_html_e('Scroll lock duration', 'minimalist-loader'); ?></label>
                                    <input id="ml-scroll-lock-duration" type="number" min="0" max="<?php echo esc_attr((string) Appearance::SCROLL_LOCK_CEILING); ?>" name="<?php echo esc_attr($option); ?>[appearance][scroll_lock_duration]" value="<?php echo esc_attr((string) $appearance->scrollLockDuration); ?>">
                                    <p class="description"><?php echo esc_html(sprintf(/* translators: %d: maximum scroll lock duration, in seconds. */ __('Counted from the moment the loader is gone. Up to %d seconds.', 'minimalist-loader'), intdiv(Appearance::SCROLL_LOCK_CEILING, 1000))); ?></p>
                                </div>
                                <div class="ml-field">
                                    <label for="ml-scroll-lock-unit"><?php esc_html_e('Unit', 'minimalist-loader'); ?></label>
                                    <select id="ml-scroll-lock-unit" name="<?php echo esc_attr($option); ?>[appearance][scroll_lock_unit]">
                                        <?php foreach (TimeUnit::cases() as $unit) : ?>
                                            <option value="<?php echo esc_attr($unit->value); ?>" <?php selected($appearance->scrollLockUnit->value, $unit->value); ?>><?php echo esc_html($unit->label()); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ml-field ml-toggle-row">
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr($option); ?>[appearance][use_blur]" value="1" <?php checked($appearance->useBlur); ?>>
                                    <?php esc_html_e('Apply subtle background blur', 'minimalist-loader'); ?>
                                </label>
                            </div>

                            <div class="ml-field">
                                <label for="ml-subtitle"><?php esc_html_e('Optional subtitle', 'minimalist-loader'); ?></label>
                                <input id="ml-subtitle" type="text" maxlength="<?php echo esc_attr((string) Appearance::SUBTITLE_MAX_LENGTH); ?>" name="<?php echo esc_attr($option); ?>[appearance][subtitle]" value="<?php echo esc_attr($appearance->subtitle); ?>" placeholder="<?php esc_attr_e('Loading content...', 'minimalist-loader'); ?>">
                            </div>

                            <div class="ml-field">
                                <span class="ml-field__label"><?php esc_html_e('Optional logo', 'minimalist-loader'); ?></span>
                                <div class="ml-logo-picker">
                                    <input type="hidden" id="ml-logo-id" name="<?php echo esc_attr($option); ?>[appearance][logo_id]" value="<?php echo esc_attr((string) $appearance->logoId); ?>">
                                    <div class="ml-logo-preview<?php echo $logoUrl !== '' ? ' has-logo' : ''; ?>">
                                        <?php if ($logoUrl !== '') : ?>
                                            <img src="<?php echo esc_url($logoUrl); ?>" alt="">
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="button" id="ml-select-logo"><?php esc_html_e('Select logo', 'minimalist-loader'); ?></button>
                                    <button type="button" class="button button-link-delete" id="ml-remove-logo"><?php esc_html_e('Remove', 'minimalist-loader'); ?></button>
                                </div>
                                <p class="description"><?php esc_html_e('Use a JPEG, PNG, WebP, or GIF image. The plugin validates the image before saving.', 'minimalist-loader'); ?></p>
                            </div>
                        </section>

                        <section class="ml-panel" id="ml-gam">
                            <div class="ml-panel__header">
                                <h2><?php esc_html_e('Google Ad Manager', 'minimalist-loader'); ?></h2>
                                <p><?php esc_html_e('Choose which ad blocks the preloader should wait for.', 'minimalist-loader'); ?></p>
                            </div>

                            <div class="ml-field">
                                <label for="ml-gam-event"><?php esc_html_e('Release timing', 'minimalist-loader'); ?></label>
                                <select id="ml-gam-event" name="<?php echo esc_attr($option); ?>[gam][event]">
                                    <?php foreach (GamEvent::cases() as $event) : ?>
                                        <option value="<?php echo esc_attr($event->value); ?>" <?php selected($gam->event->value, $event->value); ?>><?php echo esc_html($event->label()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Only "when the ad finishes rendering" fires on an unfilled block, so it is the only option that can wait out a rebid cascade.', 'minimalist-loader'); ?></p>
                            </div>

                            <div class="ml-field">
                                <label for="ml-slot-ids"><?php esc_html_e('Ad blocks to wait for', 'minimalist-loader'); ?></label>
                                <textarea id="ml-slot-ids" rows="6" name="<?php echo esc_attr($option); ?>[gam][slot_ids]" placeholder="<?php esc_attr_e("top_banner_desktop\narticle_mid_mobile", 'minimalist-loader'); ?>"><?php echo esc_textarea(implode("\n", $gam->slotIds)); ?></textarea>
                                <p class="description"><?php esc_html_e('Add one block per line. The preloader is released as soon as any listed block is ready.', 'minimalist-loader'); ?></p>
                            </div>
                        </section>

                        <section class="ml-panel" id="ml-display">
                            <div class="ml-panel__header">
                                <h2><?php esc_html_e('Where to display', 'minimalist-loader'); ?></h2>
                                <p><?php esc_html_e('Choose the site areas where the preloader can run.', 'minimalist-loader'); ?></p>
                            </div>

                            <div class="ml-check-grid">
                                <?php foreach (Location::cases() as $location) : ?>
                                    <label class="ml-check-card">
                                        <input type="checkbox" name="<?php echo esc_attr($option); ?>[display][locations][]" value="<?php echo esc_attr($location->value); ?>" <?php checked($display->includes($location)); ?>>
                                        <span><?php echo esc_html($location->label()); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="description"><?php esc_html_e('If nothing is selected, all supported areas are enabled.', 'minimalist-loader'); ?></p>
                        </section>

                        <section class="ml-panel" id="ml-exclusions">
                            <div class="ml-panel__header">
                                <h2><?php esc_html_e('Post ID exclusions', 'minimalist-loader'); ?></h2>
                                <p><?php esc_html_e('Search posts or pages, or add IDs manually to prevent the loader from running.', 'minimalist-loader'); ?></p>
                            </div>

                            <div class="ml-search-row">
                                <input type="search" id="ml-content-search" placeholder="<?php esc_attr_e('Search by title or ID', 'minimalist-loader'); ?>">
                                <input type="number" min="1" id="ml-manual-id" placeholder="<?php esc_attr_e('Manual ID', 'minimalist-loader'); ?>">
                                <button type="button" class="button" id="ml-add-manual-id"><?php esc_html_e('Add', 'minimalist-loader'); ?></button>
                            </div>

                            <div class="ml-search-results" id="ml-search-results" aria-live="polite"></div>

                            <div class="ml-selected-list" id="ml-selected-exclusions">
                                <?php foreach ($display->excludedIds as $excludedId) : ?>
                                    <?php $this->renderExclusion($excludedId); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <?php submit_button(__('Save settings', 'minimalist-loader')); ?>
                    </main>
                </div>
            </form>
        </div>
        <?php
    }

    private function renderExclusion(int $postId): void
    {
        $post = get_post($postId);
        $title = $post !== null
            ? get_the_title($post)
            /* translators: %d: post ID. */
            : sprintf(__('ID #%d', 'minimalist-loader'), $postId);
        $meta = $post !== null
            ? sprintf('%s - %s', $post->post_type, $post->post_status)
            : __('Manual ID', 'minimalist-loader');
        ?>
        <div class="ml-selected-item" data-id="<?php echo esc_attr((string) $postId); ?>">
            <input type="hidden" name="<?php echo esc_attr(Plugin::OPTION); ?>[display][excluded_ids][]" value="<?php echo esc_attr((string) $postId); ?>">
            <span>
                <strong><?php echo esc_html($title); ?></strong>
                <small><?php echo esc_html($meta); ?> - ID <?php echo esc_html((string) $postId); ?></small>
            </span>
            <button type="button" class="button-link-delete ml-remove-exclusion"><?php esc_html_e('Remove', 'minimalist-loader'); ?></button>
        </div>
        <?php
    }
}
