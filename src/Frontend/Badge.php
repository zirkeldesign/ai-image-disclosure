<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure\Frontend;

use WP_Block;
use WP_HTML_Tag_Processor;
use ZirkelDesign\AiImageDisclosure\Attachment\Flag;
use ZirkelDesign\AiImageDisclosure\Settings;

/**
 * Adds the label to blocks that show a marked image.
 *
 * The element framing the image gets `has-aiid-badge aiid--<corner>`, an
 * aria-hidden `<span class="aiid-badge">` becomes its first child,
 * and the label is appended to a non-empty alt text so screen readers get it
 * once. Only the rendered output changes; saved content is never touched, so
 * the editor never reports invalid blocks.
 */
final class Badge
{
    public function register(): void
    {
        add_filter('render_block', [$this, 'render'], 10, 3);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        // Small and loaded everywhere: enqueued only on a hit, it would print in
        // the footer of a classic theme and the label would flash unstyled.
        wp_enqueue_style('ai-image-disclosure', AI_IMAGE_DISCLOSURE_URL.'assets/css/badge.css', [], AI_IMAGE_DISCLOSURE_VERSION);
    }

    /**
     * @param  array<string, mixed>  $block
     */
    public function render(string $html, array $block, ?WP_Block $instance = null): string
    {
        if (is_admin() || wp_is_serving_rest_request()) {
            return $html;
        }

        [$id, $carrier] = self::target($block, $instance);

        if (! $id || ! Flag::isGenerated($id)) {
            return $html;
        }

        return self::decorate($html, $id, $carrier);
    }

    /**
     * The attachment a block shows, and the class of the element framing it
     * (null: the block's outermost element).
     *
     * @param  array<string, mixed>  $block
     * @return array{0: int, 1: ?string}
     */
    private static function target(array $block, ?WP_Block $instance): array
    {
        $attrs = (array) ($block['attrs'] ?? []);

        return match ($block['blockName'] ?? '') {
            'core/image' => [(int) ($attrs['id'] ?? 0), null],
            'core/cover' => [($attrs['backgroundType'] ?? 'image') === 'image' ? (int) ($attrs['id'] ?? 0) : 0, null],
            'core/media-text' => [($attrs['mediaType'] ?? '') === 'image' ? (int) ($attrs['mediaId'] ?? 0) : 0, 'wp-block-media-text__media'],
            'core/post-featured-image' => [(int) get_post_thumbnail_id((int) ($instance?->context['postId'] ?? get_the_ID())), null],
            default => [0, null],
        };
    }

    private static function decorate(string $html, int $id, ?string $carrier): string
    {
        $tags = new WP_HTML_Tag_Processor($html);
        $found = $carrier === null ? $tags->next_tag() : $tags->next_tag(['class_name' => $carrier]);

        if (! $found) {
            return $html;
        }

        $tags->add_class('has-aiid-badge');
        $tags->add_class('aiid--'.Flag::position($id)->value);

        // A caption sits inside the figure, so the frame's bottom corners would
        // land on the caption. The stylesheet then lays badge and image into one
        // grid cell instead.
        if (str_contains($html, '<figcaption')) {
            $tags->add_class('aiid--captioned');
        }

        $label = Settings::label();

        if (Settings::appendToAlt() && $tags->next_tag(['tag_name' => 'img'])) {
            $alt = trim((string) $tags->get_attribute('alt'));

            // An empty alt marks a decorative image and stays empty.
            if ($alt !== '') {
                $tags->set_attribute('alt', "{$alt} ({$label})");
            }
        }

        $badge = '<span class="aiid-badge" aria-hidden="true">'.esc_html($label).'</span>';

        // First child of the framing element: a sibling of the image, or of the
        // link around it, in every block shape. One opening tag, attributes quoted.
        $tag = '<(?:[^>"\']|"[^"]*"|\'[^\']*\')*>';
        $pattern = $carrier === null
            ? '/^\s*'.$tag.'/'
            : '/<[a-z]+\b(?=[^>]*\b'.preg_quote($carrier, '/').'\b)'.substr($tag, 1).'/i';

        return preg_replace($pattern, '$0'.$badge, $tags->get_updated_html(), 1) ?? $html;
    }
}
