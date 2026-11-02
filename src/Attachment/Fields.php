<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure\Attachment;

use WP_Post;
use ZirkelDesign\AiImageDisclosure\Position;
use ZirkelDesign\AiImageDisclosure\Settings;

/**
 * Checkbox and position on the image, in the media modal and on the edit screen.
 * Saving goes through Core's own attachment save path, which checks the nonce.
 */
final class Fields
{
    public function register(): void
    {
        add_filter('attachment_fields_to_edit', [$this, 'edit'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'save'], 10, 2);
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<string, array<string, mixed>>
     */
    public function edit(array $fields, WP_Post $post): array
    {
        if (! wp_attachment_is_image($post)) {
            return $fields;
        }

        $name = "attachments[{$post->ID}]";

        $fields['aiid_generated'] = [
            'label' => __('AI-generated', 'ai-image-disclosure'),
            'input' => 'html',
            'html' => sprintf(
                '<label><input type="checkbox" name="%s[aiid_generated]" value="1"%s> %s</label>',
                esc_attr($name),
                checked(Flag::isGenerated($post->ID), true, false),
                /* translators: %s: label text shown on the image */
                esc_html(sprintf(__('Show the label "%s" on this image', 'ai-image-disclosure'), Settings::label()))
            ),
            'helps' => $this->sourceHint($post->ID),
        ];

        $options = sprintf(
            '<option value="">%s</option>',
            /* translators: %s: the default position, e.g. "Top right" */
            esc_html(sprintf(__('Default (%s)', 'ai-image-disclosure'), Settings::defaultPosition()->label()))
        );

        foreach (Position::cases() as $position) {
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($position->value),
                selected(Flag::storedPosition($post->ID)->value ?? '', $position->value, false),
                esc_html($position->label())
            );
        }

        $fields['aiid_position'] = [
            'label' => __('Label position', 'ai-image-disclosure'),
            'input' => 'html',
            'html' => sprintf('<select name="%s[aiid_position]">%s</select>', esc_attr($name), $options),
        ];

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $post
     * @param  array<string, mixed>  $attachment
     * @return array<string, mixed>
     */
    public function save(array $post, array $attachment): array
    {
        $id = (int) ($post['ID'] ?? 0);

        if (! $id || ! current_user_can('edit_post', $id)) {
            return $post;
        }

        $wanted = ! empty($attachment['aiid_generated']);

        // Keep the detected source when the box simply stays ticked.
        if ($wanted && ! Flag::isGenerated($id)) {
            Flag::mark($id);
        }

        if (! $wanted) {
            Flag::unmark($id);
        }

        if (array_key_exists('aiid_position', $attachment)) {
            Flag::setPosition($id, Position::tryFrom(sanitize_key((string) $attachment['aiid_position'])));
        }

        return $post;
    }

    private function sourceHint(int $id): string
    {
        if (! Flag::isGenerated($id)) {
            return __('Required for realistic AI images. Ticked automatically on upload when the file declares itself as AI-generated.', 'ai-image-disclosure');
        }

        return match (Flag::source($id)) {
            'iptc' => __('Ticked on upload: the file declares itself as AI-generated in its IPTC metadata.', 'ai-image-disclosure'),
            'c2pa' => __('Ticked on upload: the file carries Content Credentials (C2PA) stating AI generation.', 'ai-image-disclosure'),
            'filename' => __('Ticked on upload because of the file name. Please check.', 'ai-image-disclosure'),
            default => __('Marked by hand.', 'ai-image-disclosure'),
        };
    }
}
