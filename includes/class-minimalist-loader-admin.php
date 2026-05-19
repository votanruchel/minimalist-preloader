<?php

if (!defined('ABSPATH')) {
    exit;
}

class Minimalist_Loader_Admin
{
    private $plugin;
    private $sanitizer;
    private $page_hook = '';

    public function __construct($plugin, $sanitizer)
    {
        $this->plugin = $plugin;
        $this->sanitizer = $sanitizer;
    }

    public function hooks()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_minimalist_loader_search_content', array($this, 'ajax_search_content'));
    }

    public function register_settings()
    {
        register_setting(
            Minimalist_Loader::SETTINGS_GROUP,
            Minimalist_Loader::OPTION_NAME,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this->sanitizer, 'sanitize'),
                'default' => $this->plugin->defaults(),
            )
        );
    }

    public function add_menu()
    {
        $this->page_hook = add_options_page(
            __('Minimalist Loader', 'minimalist-loader'),
            __('Minimalist Loader', 'minimalist-loader'),
            'manage_options',
            'minimalist-loader',
            array($this, 'render_page')
        );
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== $this->page_hook) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'minimalist-loader-admin',
            MINIMALIST_LOADER_PLUGIN_URL . 'assets/admin.css',
            array(),
            MINIMALIST_LOADER_VERSION
        );

        wp_enqueue_script(
            'minimalist-loader-admin',
            MINIMALIST_LOADER_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            MINIMALIST_LOADER_VERSION,
            true
        );

        wp_localize_script(
            'minimalist-loader-admin',
            'MinimalistLoaderAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('minimalist_loader_admin'),
                'mediaTitle' => __('Select logo', 'minimalist-loader'),
                'mediaButton' => __('Use this logo', 'minimalist-loader'),
                'searching' => __('Searching...', 'minimalist-loader'),
                'noResults' => __('No content found.', 'minimalist-loader'),
                'manualIdLabel' => __('Manual ID', 'minimalist-loader'),
                'removeLabel' => __('Remove', 'minimalist-loader'),
                'optionName' => Minimalist_Loader::OPTION_NAME,
            )
        );
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->plugin->get_settings();
        $appearance = $settings['appearance'];
        $gam = $settings['gam'];
        $display = $settings['display'];
        $locations = is_array($display['locations']) ? $display['locations'] : array();
        $excluded_ids = is_array($display['excluded_ids']) ? $display['excluded_ids'] : array();
        $logo_url = $appearance['logo_id'] ? wp_get_attachment_image_url(absint($appearance['logo_id']), 'medium') : '';
        ?>
        <div class="wrap minimalist-loader-admin">
            <h1><?php esc_html_e('Minimalist Loader', 'minimalist-loader'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields(Minimalist_Loader::SETTINGS_GROUP); ?>

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
                                <label><?php esc_html_e('Loader style', 'minimalist-loader'); ?></label>
                                <div class="ml-preset-grid">
                                    <?php
                                    $this->render_preset_option('spinner', __('Thin spinner', 'minimalist-loader'), $appearance['preset']);
                                    $this->render_preset_option('dots', __('Dots', 'minimalist-loader'), $appearance['preset']);
                                    $this->render_preset_option('bar', __('Bar', 'minimalist-loader'), $appearance['preset']);
                                    $this->render_preset_option('double-ring', __('Double ring', 'minimalist-loader'), $appearance['preset']);
                                    $this->render_preset_option('pulse', __('Pulse', 'minimalist-loader'), $appearance['preset']);
                                    ?>
                                </div>
                            </div>

                            <div class="ml-columns">
                                <div class="ml-field">
                                    <label for="ml-primary-color"><?php esc_html_e('Primary color', 'minimalist-loader'); ?></label>
                                    <input id="ml-primary-color" type="text" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][primary_color]" value="<?php echo esc_attr($appearance['primary_color']); ?>" placeholder="#111827">
                                </div>
                                <div class="ml-field">
                                    <label for="ml-secondary-color"><?php esc_html_e('Secondary color', 'minimalist-loader'); ?></label>
                                    <input id="ml-secondary-color" type="text" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][secondary_color]" value="<?php echo esc_attr($appearance['secondary_color']); ?>" placeholder="#e5e7eb">
                                </div>
                                <div class="ml-field">
                                    <label for="ml-background-color"><?php esc_html_e('Screen background', 'minimalist-loader'); ?></label>
                                    <input id="ml-background-color" type="text" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][background_color]" value="<?php echo esc_attr($appearance['background_color']); ?>" placeholder="rgba(255,255,255,0.94)">
                                </div>
                            </div>

                            <div class="ml-columns">
                                <div class="ml-field">
                                    <label for="ml-min-time"><?php esc_html_e('Minimum time (ms)', 'minimalist-loader'); ?></label>
                                    <input id="ml-min-time" type="number" min="0" max="20000" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][min_time]" value="<?php echo esc_attr($appearance['min_time']); ?>">
                                </div>
                                <div class="ml-field">
                                    <label for="ml-max-time"><?php esc_html_e('Maximum time (ms)', 'minimalist-loader'); ?></label>
                                    <input id="ml-max-time" type="number" min="200" max="30000" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][max_time]" value="<?php echo esc_attr($appearance['max_time']); ?>">
                                </div>
                                <div class="ml-field">
                                    <label for="ml-fade-duration"><?php esc_html_e('Fade out (ms)', 'minimalist-loader'); ?></label>
                                    <input id="ml-fade-duration" type="number" min="0" max="3000" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][fade_duration]" value="<?php echo esc_attr($appearance['fade_duration']); ?>">
                                </div>
                            </div>

                            <div class="ml-field ml-toggle-row">
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][use_blur]" value="1" <?php checked($appearance['use_blur'], '1'); ?>>
                                    <?php esc_html_e('Apply subtle background blur', 'minimalist-loader'); ?>
                                </label>
                            </div>

                            <div class="ml-field">
                                <label for="ml-subtitle"><?php esc_html_e('Optional subtitle', 'minimalist-loader'); ?></label>
                                <input id="ml-subtitle" type="text" maxlength="120" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][subtitle]" value="<?php echo esc_attr($appearance['subtitle']); ?>" placeholder="<?php esc_attr_e('Loading content...', 'minimalist-loader'); ?>">
                            </div>

                            <div class="ml-field">
                                <label><?php esc_html_e('Optional logo', 'minimalist-loader'); ?></label>
                                <div class="ml-logo-picker">
                                    <input type="hidden" id="ml-logo-id" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][logo_id]" value="<?php echo esc_attr(absint($appearance['logo_id'])); ?>">
                                    <div class="ml-logo-preview<?php echo $logo_url ? ' has-logo' : ''; ?>">
                                        <?php if ($logo_url) : ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="">
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
                                <select id="ml-gam-event" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[gam][event]">
                                    <option value="slotRenderEnded" <?php selected($gam['event'], 'slotRenderEnded'); ?>><?php esc_html_e('When the ad finishes rendering', 'minimalist-loader'); ?></option>
                                    <option value="slotOnload" <?php selected($gam['event'], 'slotOnload'); ?>><?php esc_html_e('When the ad fully loads', 'minimalist-loader'); ?></option>
                                    <option value="impressionViewable" <?php selected($gam['event'], 'impressionViewable'); ?>><?php esc_html_e('When the ad becomes viewable', 'minimalist-loader'); ?></option>
                                </select>
                            </div>

                            <div class="ml-field">
                                <label for="ml-slot-ids"><?php esc_html_e('Ad blocks to wait for', 'minimalist-loader'); ?></label>
                                <textarea id="ml-slot-ids" rows="6" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[gam][slot_ids]" placeholder="<?php esc_attr_e("top_banner_desktop\narticle_mid_mobile", 'minimalist-loader'); ?>"><?php echo esc_textarea(implode("\n", $gam['slot_ids'])); ?></textarea>
                                <p class="description"><?php esc_html_e('Add one block per line. The preloader is released as soon as any listed block is ready.', 'minimalist-loader'); ?></p>
                            </div>
                        </section>

                        <section class="ml-panel" id="ml-display">
                            <div class="ml-panel__header">
                                <h2><?php esc_html_e('Where to display', 'minimalist-loader'); ?></h2>
                                <p><?php esc_html_e('Choose the site areas where the preloader can run.', 'minimalist-loader'); ?></p>
                            </div>

                            <div class="ml-check-grid">
                                <?php $this->render_location_checkbox('home', __('Home', 'minimalist-loader'), $locations); ?>
                                <?php $this->render_location_checkbox('posts', __('Posts', 'minimalist-loader'), $locations); ?>
                                <?php $this->render_location_checkbox('pages', __('Pages', 'minimalist-loader'), $locations); ?>
                                <?php $this->render_location_checkbox('categories', __('Categories', 'minimalist-loader'), $locations); ?>
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
                                <?php foreach ($excluded_ids as $excluded_id) : ?>
                                    <?php $this->render_exclusion_item($excluded_id); ?>
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

    public function ajax_search_content()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'minimalist-loader')), 403);
        }

        check_ajax_referer('minimalist_loader_admin', 'nonce');

        $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
        $results = array();

        if ($term !== '' && ctype_digit($term)) {
            $post = get_post(absint($term));

            if ($post && in_array($post->post_type, array('post', 'page'), true)) {
                $results[$post->ID] = $this->format_post_result($post);
            }
        }

        if ($term !== '') {
            $query = new WP_Query(array(
                's' => $term,
                'post_type' => array('post', 'page'),
                'post_status' => array('publish', 'draft', 'pending', 'private', 'future'),
                'posts_per_page' => 20,
                'no_found_rows' => true,
                'orderby' => 'date',
                'order' => 'DESC',
            ));

            foreach ($query->posts as $post) {
                $results[$post->ID] = $this->format_post_result($post);
            }
        }

        wp_send_json_success(array('results' => array_values($results)));
    }

    private function render_preset_option($value, $label, $selected)
    {
        ?>
        <label class="ml-preset-option">
            <input type="radio" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[appearance][preset]" value="<?php echo esc_attr($value); ?>" <?php checked($selected, $value); ?>>
            <span class="ml-preset-preview ml-preset-preview--<?php echo esc_attr($value); ?>" aria-hidden="true"><i></i><i></i><i></i></span>
            <strong><?php echo esc_html($label); ?></strong>
        </label>
        <?php
    }

    private function render_location_checkbox($value, $label, $locations)
    {
        ?>
        <label class="ml-check-card">
            <input type="checkbox" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[display][locations][]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value, $locations, true)); ?>>
            <span><?php echo esc_html($label); ?></span>
        </label>
        <?php
    }

    private function render_exclusion_item($post_id)
    {
        $post_id = absint($post_id);
        $post = get_post($post_id);
        $title = $post ? get_the_title($post) : sprintf(__('ID #%d', 'minimalist-loader'), $post_id);
        $meta = $post ? sprintf('%s - %s', $post->post_type, $post->post_status) : __('Manual ID', 'minimalist-loader');
        ?>
        <div class="ml-selected-item" data-id="<?php echo esc_attr($post_id); ?>">
            <input type="hidden" name="<?php echo esc_attr(Minimalist_Loader::OPTION_NAME); ?>[display][excluded_ids][]" value="<?php echo esc_attr($post_id); ?>">
            <span>
                <strong><?php echo esc_html($title); ?></strong>
                <small><?php echo esc_html($meta); ?> - ID <?php echo esc_html($post_id); ?></small>
            </span>
            <button type="button" class="button-link-delete ml-remove-exclusion"><?php esc_html_e('Remove', 'minimalist-loader'); ?></button>
        </div>
        <?php
    }

    private function format_post_result($post)
    {
        return array(
            'id' => absint($post->ID),
            'title' => get_the_title($post) ? get_the_title($post) : sprintf(__('Untitled #%d', 'minimalist-loader'), $post->ID),
            'meta' => sprintf('%s - %s', $post->post_type, $post->post_status),
        );
    }
}
