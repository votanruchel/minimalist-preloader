<?php

declare(strict_types=1);

namespace MinimalistLoader\Admin;

use MinimalistLoader\Plugin;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

/** Backs the exclusion picker: searches posts and pages by title or ID. */
final class SearchController
{
    public const ROUTE = '/content-search';

    private const POST_TYPES = ['post', 'page'];
    private const MAX_RESULTS = 20;

    public function __construct(private readonly Plugin $plugin)
    {
    }

    public function register(): void
    {
        add_action('rest_api_init', $this->registerRoutes(...));
    }

    public static function url(): string
    {
        return rest_url(Plugin::REST_NAMESPACE . self::ROUTE);
    }

    public function registerRoutes(): void
    {
        register_rest_route(Plugin::REST_NAMESPACE, self::ROUTE, [
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->search(...),
            'permission_callback' => static fn (): bool => current_user_can('manage_options'),
            'args' => [
                'term' => [
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    public function search(WP_REST_Request $request): WP_REST_Response
    {
        $term = trim((string) $request->get_param('term'));

        if ($term === '') {
            return new WP_REST_Response(['results' => []]);
        }

        $results = [];

        // A numeric term is treated as an ID first so an exact match always leads.
        if (ctype_digit($term)) {
            $post = get_post((int) $term);

            if ($post instanceof WP_Post && in_array($post->post_type, self::POST_TYPES, true)) {
                $results[$post->ID] = $this->format($post);
            }
        }

        $query = new WP_Query([
            's' => $term,
            'post_type' => self::POST_TYPES,
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => self::MAX_RESULTS,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($query->posts as $post) {
            $results[$post->ID] = $this->format($post);
        }

        return new WP_REST_Response(['results' => array_values($results)]);
    }

    /** @return array{id: int, title: string, meta: string} */
    private function format(WP_Post $post): array
    {
        $title = get_the_title($post);

        return [
            'id' => (int) $post->ID,
            'title' => $title !== ''
                ? $title
                /* translators: %d: post ID. */
                : sprintf(__('Untitled #%d', 'minimalist-loader'), $post->ID),
            'meta' => sprintf('%s - %s', $post->post_type, $post->post_status),
        ];
    }
}
