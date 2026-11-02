<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure\Attachment;

/**
 * A column in the media list view and two bulk actions. Core checks the
 * list table nonce before handle_bulk_actions-upload runs; the capability is
 * checked per image here.
 */
final class Library
{
    private const MARK = 'aiid_mark';

    private const UNMARK = 'aiid_unmark';

    public function register(): void
    {
        add_filter('manage_media_columns', [$this, 'columns']);
        add_action('manage_media_custom_column', [$this, 'column'], 10, 2);
        add_filter('bulk_actions-upload', [$this, 'actions']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle'], 10, 3);
        add_action('admin_notices', [$this, 'notice']);
    }

    /**
     * @param  array<string, string>  $columns
     * @return array<string, string>
     */
    public function columns(array $columns): array
    {
        $columns['aiid'] = __('AI-generated', 'ai-image-disclosure');

        return $columns;
    }

    public function column(string $column, int $id): void
    {
        if ($column !== 'aiid' || ! Flag::isGenerated($id)) {
            return;
        }

        printf(
            '<span class="dashicons dashicons-yes" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
            esc_html__('Marked as AI-generated', 'ai-image-disclosure')
        );
    }

    /**
     * @param  array<string, string>  $actions
     * @return array<string, string>
     */
    public function actions(array $actions): array
    {
        $actions[self::MARK] = __('Mark as AI-generated', 'ai-image-disclosure');
        $actions[self::UNMARK] = __('Remove AI marking', 'ai-image-disclosure');

        return $actions;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function handle(string $redirect, string $action, array $ids): string
    {
        if ($action !== self::MARK && $action !== self::UNMARK) {
            return $redirect;
        }

        $done = 0;

        foreach (array_map('intval', $ids) as $id) {
            if (! current_user_can('edit_post', $id) || ! wp_attachment_is_image($id)) {
                continue;
            }

            $action === self::MARK ? Flag::mark($id) : Flag::unmark($id);
            $done++;
        }

        return add_query_arg(['aiid_done' => $done, 'aiid_action' => $action], $redirect);
    }

    public function notice(): void
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display of the count Core redirected back with.
        if (! isset($_GET['aiid_done'], $_GET['aiid_action'])) {
            return;
        }

        $done = absint($_GET['aiid_done']);
        $mark = sanitize_key(wp_unslash($_GET['aiid_action'])) === self::MARK;
        // phpcs:enable

        $message = $mark
            /* translators: %d: number of images */
            ? _n('%d image marked as AI-generated.', '%d images marked as AI-generated.', $done, 'ai-image-disclosure')
            /* translators: %d: number of images */
            : _n('AI marking removed from %d image.', 'AI marking removed from %d images.', $done, 'ai-image-disclosure');

        printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html(sprintf($message, $done)));
    }
}
