<?php

if (!defined('ABSPATH')) {
    exit;
}

class Minimalist_Loader_Sanitizer
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function sanitize($input)
    {
        $defaults = $this->plugin->defaults();

        if (!is_array($input)) {
            return $defaults;
        }

        $appearance = isset($input['appearance']) && is_array($input['appearance']) ? $input['appearance'] : array();
        $gam = isset($input['gam']) && is_array($input['gam']) ? $input['gam'] : array();
        $display = isset($input['display']) && is_array($input['display']) ? $input['display'] : array();

        return array(
            'appearance' => array(
                'preset' => $this->sanitize_preset(isset($appearance['preset']) ? $appearance['preset'] : $defaults['appearance']['preset']),
                'primary_color' => $this->sanitize_css_color(isset($appearance['primary_color']) ? $appearance['primary_color'] : $defaults['appearance']['primary_color'], $defaults['appearance']['primary_color']),
                'secondary_color' => $this->sanitize_css_color(isset($appearance['secondary_color']) ? $appearance['secondary_color'] : $defaults['appearance']['secondary_color'], $defaults['appearance']['secondary_color']),
                'background_color' => $this->sanitize_css_color(isset($appearance['background_color']) ? $appearance['background_color'] : $defaults['appearance']['background_color'], $defaults['appearance']['background_color']),
                'use_blur' => empty($appearance['use_blur']) ? '0' : '1',
                'logo_id' => $this->sanitize_logo_id(isset($appearance['logo_id']) ? $appearance['logo_id'] : 0),
                'subtitle' => $this->sanitize_limited_text(isset($appearance['subtitle']) ? $appearance['subtitle'] : '', 120),
                'min_time' => $this->sanitize_int(isset($appearance['min_time']) ? $appearance['min_time'] : $defaults['appearance']['min_time'], 0, 20000),
                'max_time' => $this->sanitize_int(isset($appearance['max_time']) ? $appearance['max_time'] : $defaults['appearance']['max_time'], 200, 30000),
                'fade_duration' => $this->sanitize_int(isset($appearance['fade_duration']) ? $appearance['fade_duration'] : $defaults['appearance']['fade_duration'], 0, 3000),
            ),
            'gam' => array(
                'event' => $this->sanitize_gam_event(isset($gam['event']) ? $gam['event'] : $defaults['gam']['event']),
                'slot_ids' => $this->sanitize_slot_ids(isset($gam['slot_ids']) ? $gam['slot_ids'] : array()),
            ),
            'display' => array(
                'locations' => $this->sanitize_locations(isset($display['locations']) ? $display['locations'] : $defaults['display']['locations']),
                'excluded_ids' => $this->sanitize_ids(isset($display['excluded_ids']) ? $display['excluded_ids'] : array()),
            ),
        );
    }

    public function validate_logo_attachment($attachment_id)
    {
        $attachment_id = absint($attachment_id);

        if (!$attachment_id) {
            return false;
        }

        if (get_post_type($attachment_id) !== 'attachment') {
            return false;
        }

        $file = get_attached_file($attachment_id);

        if (!$file || !is_readable($file)) {
            return false;
        }

        $allowed_mimes = array(
            'jpg|jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        );

        $checked = wp_check_filetype_and_ext($file, wp_basename($file), $allowed_mimes);
        $real_mime = function_exists('wp_get_image_mime') ? wp_get_image_mime($file) : '';
        $post_mime = get_post_mime_type($attachment_id);

        if (empty($checked['type']) || !in_array($checked['type'], $allowed_mimes, true)) {
            return false;
        }

        if ($real_mime && $real_mime !== $checked['type']) {
            return false;
        }

        if ($post_mime && $post_mime !== $checked['type']) {
            return false;
        }

        if (!@getimagesize($file)) {
            return false;
        }

        return $this->passes_magic_bytes($file, $checked['type']);
    }

    private function sanitize_preset($preset)
    {
        $preset = sanitize_key($preset);
        $allowed = array('spinner', 'dots', 'bar', 'double-ring', 'pulse');

        return in_array($preset, $allowed, true) ? $preset : 'spinner';
    }

    private function sanitize_gam_event($event)
    {
        $event = strtolower(preg_replace('/[^A-Za-z]/', '', (string) $event));
        $allowed = array(
            'slotrenderended' => 'slotRenderEnded',
            'slotonload' => 'slotOnload',
            'impressionviewable' => 'impressionViewable',
        );

        return isset($allowed[$event]) ? $allowed[$event] : 'slotRenderEnded';
    }

    private function sanitize_css_color($value, $default)
    {
        $value = trim((string) $value);
        $hex = sanitize_hex_color($value);

        if ($hex) {
            return $hex;
        }

        if (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0|1|0?\.\d+))?\s*\)$/', $value, $matches)) {
            $red = (int) $matches[1];
            $green = (int) $matches[2];
            $blue = (int) $matches[3];

            if ($red <= 255 && $green <= 255 && $blue <= 255) {
                return $value;
            }
        }

        return $default;
    }

    private function sanitize_logo_id($attachment_id)
    {
        $attachment_id = absint($attachment_id);

        if (!$attachment_id) {
            return 0;
        }

        if ($this->validate_logo_attachment($attachment_id)) {
            return $attachment_id;
        }

        add_settings_error(
            Minimalist_Loader::OPTION_NAME,
            'minimalist_loader_invalid_logo',
            __('The selected logo did not pass validation and was removed.', 'minimalist-loader'),
            'error'
        );

        return 0;
    }

    private function sanitize_limited_text($value, $limit)
    {
        $value = sanitize_text_field($value);

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit);
        }

        return substr($value, 0, $limit);
    }

    private function sanitize_int($value, $min, $max)
    {
        $value = absint($value);

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }

    private function sanitize_slot_ids($value)
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value);
        }

        if (!is_array($value)) {
            return array();
        }

        $slot_ids = array();

        foreach ($value as $slot_id) {
            $slot_id = sanitize_text_field($slot_id);
            $slot_id = preg_replace('/[^A-Za-z0-9_\-:\.]/', '', $slot_id);
            $slot_id = strtolower($slot_id);
            $slot_id = substr($slot_id, 0, 160);

            if ($slot_id !== '') {
                $slot_ids[] = $slot_id;
            }
        }

        return array_slice(array_values(array_unique($slot_ids)), 0, 50);
    }

    private function sanitize_locations($value)
    {
        if (!is_array($value)) {
            return array();
        }

        $allowed = array('home', 'posts', 'pages', 'categories');
        $locations = array();

        foreach ($value as $location) {
            $location = sanitize_key($location);

            if (in_array($location, $allowed, true)) {
                $locations[] = $location;
            }
        }

        return array_values(array_unique($locations));
    }

    private function sanitize_ids($value)
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value);
        }

        if (!is_array($value)) {
            return array();
        }

        $ids = array();

        foreach ($value as $id) {
            $id = absint($id);

            if ($id) {
                $ids[] = $id;
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, 500);
    }

    private function passes_magic_bytes($file, $mime)
    {
        $handle = @fopen($file, 'rb');

        if (!$handle) {
            return false;
        }

        $bytes = fread($handle, 16);
        fclose($handle);

        if ($mime === 'image/jpeg') {
            return strlen($bytes) >= 3 && ord($bytes[0]) === 255 && ord($bytes[1]) === 216 && ord($bytes[2]) === 255;
        }

        if ($mime === 'image/png') {
            $png = chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10);
            return substr($bytes, 0, 8) === $png;
        }

        if ($mime === 'image/gif') {
            $header = substr($bytes, 0, 6);
            return $header === 'GIF87a' || $header === 'GIF89a';
        }

        if ($mime === 'image/webp') {
            return substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP';
        }

        return false;
    }
}
