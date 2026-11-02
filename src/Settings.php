<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure;

/**
 * All options live on Settings > Media, in a section of their own. The plugin
 * has no menu page of its own: the settings belong with the other media settings.
 */
final class Settings
{
    public const LABEL = 'aiid_label';

    public const POSITION = 'aiid_position';

    public const DETECT = 'aiid_detect';

    public const FILENAME = 'aiid_filename';

    public const ALT = 'aiid_alt';

    public static function label(): string
    {
        $label = trim((string) get_option(self::LABEL, ''));

        return $label !== '' ? $label : __('AI-generated', 'ai-image-disclosure');
    }

    public static function defaultPosition(): Position
    {
        return Position::tryFrom((string) get_option(self::POSITION, '')) ?? Position::TopRight;
    }

    public static function detectOnUpload(): bool
    {
        return self::toggle(self::DETECT, true);
    }

    public static function detectByFilename(): bool
    {
        return self::toggle(self::FILENAME, false);
    }

    public static function appendToAlt(): bool
    {
        return self::toggle(self::ALT, true);
    }

    /**
     * Toggles are stored as "1" or "0", never as a boolean: update_option() with
     * false on an option that does not exist yet stores nothing, so a box
     * switched off would silently fall back to its default.
     */
    private static function toggle(string $name, bool $default): bool
    {
        $value = get_option($name, null);

        return $value === null ? $default : in_array($value, ['1', 1, true], true);
    }

    public function register(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void
    {
        $toggle = static fn (mixed $value): string => in_array($value, ['1', 1, true], true) ? '1' : '0';

        register_setting('media', self::LABEL, ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '']);
        register_setting('media', self::POSITION, [
            'type' => 'string',
            'default' => Position::TopRight->value,
            'sanitize_callback' => static fn (mixed $value): string => (Position::tryFrom((string) $value) ?? Position::TopRight)->value,
        ]);
        register_setting('media', self::DETECT, ['type' => 'string', 'sanitize_callback' => $toggle]);
        register_setting('media', self::FILENAME, ['type' => 'string', 'sanitize_callback' => $toggle]);
        register_setting('media', self::ALT, ['type' => 'string', 'sanitize_callback' => $toggle]);

        add_settings_section(
            'aiid',
            __('AI-generated images', 'ai-image-disclosure'),
            static function (): void {
                echo '<p>'.esc_html__('Images marked as AI-generated in the media library show a small label wherever they appear in a block.', 'ai-image-disclosure').'</p>';
            },
            'media'
        );

        add_settings_field(self::LABEL, __('Label text', 'ai-image-disclosure'), [$this, 'renderLabel'], 'media', 'aiid', ['label_for' => self::LABEL]);
        add_settings_field(self::POSITION, __('Default position', 'ai-image-disclosure'), [$this, 'renderPosition'], 'media', 'aiid', ['label_for' => self::POSITION]);
        add_settings_field('aiid_options', __('Options', 'ai-image-disclosure'), [$this, 'renderToggles'], 'media', 'aiid');
    }

    public function renderLabel(): void
    {
        printf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="%3$s"><p class="description">%4$s</p>',
            esc_attr(self::LABEL),
            esc_attr((string) get_option(self::LABEL, '')),
            esc_attr__('AI-generated', 'ai-image-disclosure'),
            esc_html__('Shown on every marked image. Leave empty for the translated default. Keep it plain: the label has to be understood at a glance.', 'ai-image-disclosure')
        );
    }

    public function renderPosition(): void
    {
        printf('<select id="%1$s" name="%1$s">', esc_attr(self::POSITION));

        foreach (Position::cases() as $position) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($position->value),
                selected(self::defaultPosition()->value, $position->value, false),
                esc_html($position->label())
            );
        }

        echo '</select><p class="description">'.esc_html__('Applies to every image left on "Default". Changing it moves all of those labels at once.', 'ai-image-disclosure').'</p>';
    }

    public function renderToggles(): void
    {
        $toggles = [
            self::DETECT => [self::detectOnUpload(), __('Mark uploads whose file declares itself as AI-generated (IPTC or C2PA)', 'ai-image-disclosure')],
            self::FILENAME => [self::detectByFilename(), __('Also mark uploads whose file name contains "ai" as a word of its own', 'ai-image-disclosure')],
            self::ALT => [self::appendToAlt(), __('Add the label to the alternative text, so screen readers announce it', 'ai-image-disclosure')],
        ];

        echo '<fieldset>';

        foreach ($toggles as $name => [$checked, $text]) {
            printf(
                '<label><input type="hidden" name="%1$s" value="0"><input type="checkbox" name="%1$s" value="1"%2$s> %3$s</label><br>',
                esc_attr($name),
                checked($checked, true, false),
                esc_html($text)
            );
        }

        echo '</fieldset>';
    }
}
