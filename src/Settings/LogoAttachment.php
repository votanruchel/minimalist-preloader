<?php

declare(strict_types=1);

namespace MinimalistLoader\Settings;

use MinimalistLoader\Plugin;

defined('ABSPATH') || exit;

/**
 * Guards the optional logo attachment.
 *
 * An attachment ID is only trusted once the file on disk agrees with the declared
 * post mime type, the extension, and its own magic bytes — a mismatch between any
 * two of those is how a non-image gets rendered into the page as one.
 */
final class LogoAttachment
{
    private const ALLOWED_MIMES = [
        'jpg|jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    /** Rejects the attachment (returning 0) and surfaces a settings error when it fails validation. */
    public static function coerce(mixed $value): int
    {
        $attachmentId = absint($value);

        if ($attachmentId === 0 || self::isValid($attachmentId)) {
            return $attachmentId;
        }

        // sanitize_option_* fires on any update_option() call, including from contexts
        // where wp-admin/includes/template.php was never loaded.
        if (function_exists('add_settings_error')) {
            add_settings_error(
                Plugin::OPTION,
                'minimalist_loader_invalid_logo',
                __('The selected logo did not pass validation and was removed.', 'minimalist-loader'),
                'error'
            );
        }

        return 0;
    }

    public static function isValid(int $attachmentId): bool
    {
        if ($attachmentId <= 0 || get_post_type($attachmentId) !== 'attachment') {
            return false;
        }

        $file = get_attached_file($attachmentId);

        if (!is_string($file) || $file === '' || !is_readable($file)) {
            return false;
        }

        $checked = wp_check_filetype_and_ext($file, wp_basename($file), self::ALLOWED_MIMES);
        $type = $checked['type'] ?? '';

        if (!is_string($type) || !in_array($type, self::ALLOWED_MIMES, true)) {
            return false;
        }

        $realMime = wp_get_image_mime($file);

        if (is_string($realMime) && $realMime !== $type) {
            return false;
        }

        $postMime = get_post_mime_type($attachmentId);

        if (is_string($postMime) && $postMime !== $type) {
            return false;
        }

        return self::isDecodableImage($file) && self::hasMagicBytes($file, $type);
    }

    private static function isDecodableImage(string $file): bool
    {
        // getimagesize() warns instead of throwing on a malformed file, hence the silencer.
        return @getimagesize($file) !== false;
    }

    private static function hasMagicBytes(string $file, string $mime): bool
    {
        $bytes = @file_get_contents($file, false, null, 0, 16);

        if (!is_string($bytes) || $bytes === '') {
            return false;
        }

        return match ($mime) {
            'image/jpeg' => str_starts_with($bytes, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($bytes, "\x89PNG\r\n\x1A\n"),
            'image/gif' => str_starts_with($bytes, 'GIF87a') || str_starts_with($bytes, 'GIF89a'),
            'image/webp' => str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP',
            default => false,
        };
    }
}
