<?php

declare(strict_types=1);

namespace MinimalistLoader\Frontend;

use MinimalistLoader\Plugin;
use MinimalistLoader\Settings\Appearance;

defined('ABSPATH') || exit;

final class Preloader
{
    public const CONTAINER_ID = 'minimalist-loader-container';

    private bool $rendered = false;
    private ?bool $canRun = null;

    public function __construct(private readonly Plugin $plugin)
    {
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', $this->enqueueAssets(...), 1);
        // Themes that skip wp_body_open still get the markup, just later in the document.
        add_action('wp_body_open', $this->render(...), 1);
        add_action('wp_footer', $this->render(...), 1);
    }

    public function enqueueAssets(): void
    {
        if (!$this->shouldRun()) {
            return;
        }

        $settings = $this->plugin->settings->get();

        wp_enqueue_style(
            'minimalist-loader-frontend',
            $this->plugin->url('assets/frontend.css'),
            [],
            $this->plugin->version
        );

        wp_add_inline_style('minimalist-loader-frontend', $this->customProperties($settings->appearance));

        // Deliberately render-blocking in <head>: the script locks scrolling before the
        // theme paints, and deferring it would let the page flash through unlocked.
        wp_enqueue_script(
            'minimalist-loader-frontend',
            $this->plugin->url('assets/frontend.js'),
            [],
            $this->plugin->version,
            ['in_footer' => false]
        );

        wp_add_inline_script(
            'minimalist-loader-frontend',
            sprintf('window.MinimalistLoaderConfig=%s;', wp_json_encode([
                'event' => $settings->gam->event->value,
                'slotIds' => $settings->gam->slotIds,
                'minTime' => $settings->appearance->minTime,
                'maxTime' => $settings->appearance->maxTime,
                'fadeDuration' => $settings->appearance->fadeDuration,
                'scrollLock' => $settings->appearance->scrollLockMilliseconds(),
                'containerId' => self::CONTAINER_ID,
            ]) ?: '{}'),
            'before'
        );
    }

    public function render(): void
    {
        if ($this->rendered || !$this->shouldRun()) {
            return;
        }

        $this->rendered = true;

        $appearance = $this->plugin->settings->get()->appearance;
        $logo = $appearance->logoId > 0
            ? wp_get_attachment_image($appearance->logoId, 'medium', false, [
                'class' => 'minimalist-loader__logo',
                'alt' => '',
            ])
            : '';
        ?>
        <div id="<?php echo esc_attr(self::CONTAINER_ID); ?>" class="minimalist-loader minimalist-loader--<?php echo esc_attr($appearance->preset->value); ?>" role="status" aria-live="polite" aria-label="<?php esc_attr_e('Loading content', 'minimalist-loader'); ?>">
            <div class="minimalist-loader__inner">
                <?php if ($logo !== '') : ?>
                    <div class="minimalist-loader__brand"><?php echo $logo; ?></div>
                <?php endif; ?>

                <div class="minimalist-loader__mark" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>

                <?php if ($appearance->subtitle !== '') : ?>
                    <p class="minimalist-loader__subtitle"><?php echo esc_html($appearance->subtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        wp_print_inline_script_tag($this->reconcileScript());
    }

    /**
     * The loader can be released before this markup exists — a fast fill or a tiny max
     * time both resolve while the document is still parsing the head. This reconciles
     * the container with the state the head script already settled on.
     */
    private function reconcileScript(): string
    {
        return sprintf(
            <<<'JS'
            {
              const root = document.documentElement;
              if (root.classList.contains('minimalist-loader-released')) {
                document.getElementById(%s)?.remove();
                root.classList.remove('minimalist-loader-active');
              } else {
                root.classList.add('minimalist-loader-active');
              }
            }
            JS,
            wp_json_encode(self::CONTAINER_ID)
        );
    }

    private function customProperties(Appearance $appearance): string
    {
        // Every value below is allowlisted during hydration, which is also what keeps
        // it from breaking out of this <style> block.
        $properties = sprintf(
            ':root{--ml-primary:%s;--ml-secondary:%s;--ml-bg:%s;--ml-fade:%dms;--ml-blur:%dpx;}',
            $appearance->primaryColor,
            $appearance->secondaryColor,
            $appearance->backgroundColor,
            $appearance->fadeDuration,
            $appearance->blurRadius
        );

        if (!$appearance->useBlur) {
            $properties .= sprintf('#%s{backdrop-filter:none;-webkit-backdrop-filter:none;}', self::CONTAINER_ID);
        }

        return $properties;
    }

    private function shouldRun(): bool
    {
        return $this->canRun ??= $this->resolveShouldRun();
    }

    private function resolveShouldRun(): bool
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_preview() || is_embed()) {
            return false;
        }

        return $this->plugin->settings->get()->display->allows((int) get_queried_object_id());
    }
}
