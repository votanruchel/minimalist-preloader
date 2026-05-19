<?php

if (!defined('ABSPATH')) {
    exit;
}

class Minimalist_Loader_Frontend
{
    private $plugin;
    private $rendered = false;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function hooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 1);
        add_action('wp_body_open', array($this, 'render_loader'), 1);
        add_action('wp_footer', array($this, 'render_loader'), 1);
    }

    public function enqueue_assets()
    {
        if (!$this->should_run()) {
            return;
        }

        $settings = $this->plugin->get_settings();
        $appearance = $settings['appearance'];
        $gam = $settings['gam'];

        wp_enqueue_style(
            'minimalist-loader-frontend',
            MINIMALIST_LOADER_PLUGIN_URL . 'assets/frontend.css',
            array(),
            MINIMALIST_LOADER_VERSION
        );

        $custom_css = sprintf(
            ':root{--ml-primary:%1$s;--ml-secondary:%2$s;--ml-bg:%3$s;--ml-fade:%4$dms;}',
            esc_html($appearance['primary_color']),
            esc_html($appearance['secondary_color']),
            esc_html($appearance['background_color']),
            absint($appearance['fade_duration'])
        );

        if ($appearance['use_blur'] !== '1') {
            $custom_css .= '#minimalist-loader-container{backdrop-filter:none;-webkit-backdrop-filter:none;}';
        }

        wp_add_inline_style('minimalist-loader-frontend', $custom_css);

        wp_enqueue_script(
            'minimalist-loader-frontend',
            MINIMALIST_LOADER_PLUGIN_URL . 'assets/frontend.js',
            array(),
            MINIMALIST_LOADER_VERSION . '-' . filemtime(MINIMALIST_LOADER_PLUGIN_DIR . 'assets/frontend.js'),
            false
        );

        wp_add_inline_script(
            'minimalist-loader-frontend',
            'window.MinimalistLoaderConfig=' . wp_json_encode(array(
                'event' => $gam['event'],
                'slotIds' => array_values(array_map('strtolower', $gam['slot_ids'])),
                'minTime' => absint($appearance['min_time']),
                'maxTime' => absint($appearance['max_time']),
                'fadeDuration' => absint($appearance['fade_duration']),
            )) . ';',
            'before'
        );
    }

    public function render_loader()
    {
        if ($this->rendered) {
            return;
        }

        if (!$this->should_run()) {
            return;
        }

        $this->rendered = true;

        $settings = $this->plugin->get_settings();
        $appearance = $settings['appearance'];
        $preset = sanitize_html_class($appearance['preset']);
        $subtitle = trim($appearance['subtitle']);
        $logo_id = absint($appearance['logo_id']);
        $logo = $logo_id ? wp_get_attachment_image($logo_id, 'medium', false, array('class' => 'minimalist-loader__logo', 'alt' => '')) : '';
        ?>
        <div id="minimalist-loader-container" class="minimalist-loader minimalist-loader--<?php echo esc_attr($preset); ?>" role="status" aria-live="polite" aria-label="<?php esc_attr_e('Loading content', 'minimalist-loader'); ?>">
            <div class="minimalist-loader__inner">
                <?php if ($logo) : ?>
                    <div class="minimalist-loader__brand"><?php echo $logo; ?></div>
                <?php endif; ?>

                <div class="minimalist-loader__mark" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>

                <?php if ($subtitle !== '') : ?>
                    <p class="minimalist-loader__subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <script>
            (function () {
                var root = document.documentElement;
                var container = document.getElementById('minimalist-loader-container');

                if (root.classList.contains('minimalist-loader-released')) {
                    if (container && container.parentNode) {
                        container.parentNode.removeChild(container);
                    }
                    root.classList.remove('minimalist-loader-active');
                    return;
                }

                root.classList.add('minimalist-loader-active');
            })();
        </script>
        <?php
    }

    private function should_run()
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_preview()) {
            return false;
        }

        $settings = $this->plugin->get_settings();
        $display = $settings['display'];
        $locations = is_array($display['locations']) ? $display['locations'] : array();

        if (empty($locations)) {
            $locations = array('home', 'posts', 'pages', 'categories');
        }

        $queried_id = absint(get_queried_object_id());
        $excluded_ids = is_array($display['excluded_ids']) ? array_map('absint', $display['excluded_ids']) : array();

        if ($queried_id && in_array($queried_id, $excluded_ids, true)) {
            return false;
        }

        if ((is_front_page() || is_home()) && in_array('home', $locations, true)) {
            return true;
        }

        if (is_singular('post') && in_array('posts', $locations, true)) {
            return true;
        }

        if (is_page() && in_array('pages', $locations, true)) {
            return true;
        }

        if (is_category() && in_array('categories', $locations, true)) {
            return true;
        }

        return false;
    }
}
