<?php

if (!defined('ABSPATH')) {
    exit;
}

class Minimalist_Loader
{
    const OPTION_NAME = 'minimalist_loader_settings';
    const SETTINGS_GROUP = 'minimalist_loader_settings_group';

    private $settings = null;
    private $sanitizer;
    private $admin;
    private $frontend;

    public function __construct()
    {
        $this->sanitizer = new Minimalist_Loader_Sanitizer($this);
        $this->admin = new Minimalist_Loader_Admin($this, $this->sanitizer);
        $this->frontend = new Minimalist_Loader_Frontend($this);

        $this->admin->hooks();
        $this->frontend->hooks();
    }

    public function defaults()
    {
        return array(
            'appearance' => array(
                'preset' => 'spinner',
                'primary_color' => '#111827',
                'secondary_color' => '#e5e7eb',
                'background_color' => 'rgba(255,255,255,0.94)',
                'use_blur' => '1',
                'logo_id' => 0,
                'subtitle' => '',
                'min_time' => 400,
                'max_time' => 4000,
                'fade_duration' => 240,
            ),
            'gam' => array(
                'event' => 'slotRenderEnded',
                'slot_ids' => array(),
            ),
            'display' => array(
                'locations' => array('home', 'posts', 'pages', 'categories'),
                'excluded_ids' => array(),
            ),
        );
    }

    public function get_settings()
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $stored = get_option(self::OPTION_NAME, null);

        if (!is_array($stored)) {
            $stored = $this->legacy_settings();
        }

        $this->settings = $this->merge_settings($this->defaults(), $stored);

        return $this->settings;
    }

    public function get($section, $key, $default = null)
    {
        $settings = $this->get_settings();

        if (isset($settings[$section]) && is_array($settings[$section]) && array_key_exists($key, $settings[$section])) {
            return $settings[$section][$key];
        }

        return $default;
    }

    public function merge_settings($defaults, $settings)
    {
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $settings)) {
                $settings[$key] = $value;
                continue;
            }

            if (is_array($value) && is_array($settings[$key])) {
                $settings[$key] = $this->merge_settings($value, $settings[$key]);
            }
        }

        return $settings;
    }

    private function legacy_settings()
    {
        $defaults = $this->defaults();

        $defaults['appearance']['primary_color'] = get_option('minimalist_loader_spinner_color', $defaults['appearance']['primary_color']);
        $defaults['appearance']['secondary_color'] = get_option('minimalist_loader_spinner_bg_color', $defaults['appearance']['secondary_color']);
        $defaults['appearance']['background_color'] = get_option('minimalist_loader_background_color', $defaults['appearance']['background_color']);
        $defaults['appearance']['use_blur'] = get_option('minimalist_loader_use_blur', $defaults['appearance']['use_blur']);
        $defaults['appearance']['min_time'] = absint(get_option('minimalist_loader_min_time', $defaults['appearance']['min_time']));
        $defaults['appearance']['max_time'] = absint(get_option('minimalist_loader_max_time', $defaults['appearance']['max_time']));
        $defaults['gam']['event'] = get_option('minimalist_loader_gpt_event', $defaults['gam']['event']);

        return $defaults;
    }
}
