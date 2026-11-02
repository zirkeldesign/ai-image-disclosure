<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure\Attachment;

use ZirkelDesign\AiImageDisclosure\Position;
use ZirkelDesign\AiImageDisclosure\Settings;

/**
 * The marking lives on the attachment, so every place the image is used
 * follows it. A position is stored only when one is picked; everything else
 * follows the site default.
 */
final class Flag
{
    public const GENERATED = '_aiid_generated';

    public const POSITION = '_aiid_position';

    public const SOURCE = '_aiid_source';

    public const SOURCES = ['manual', 'iptc', 'c2pa', 'filename'];

    public static function isGenerated(int $id): bool
    {
        return $id > 0 && (bool) get_post_meta($id, self::GENERATED, true);
    }

    public static function storedPosition(int $id): ?Position
    {
        return Position::tryFrom((string) get_post_meta($id, self::POSITION, true));
    }

    public static function position(int $id): Position
    {
        return self::storedPosition($id) ?? Settings::defaultPosition();
    }

    public static function source(int $id): string
    {
        return (string) get_post_meta($id, self::SOURCE, true);
    }

    public static function mark(int $id, string $source = 'manual'): void
    {
        update_post_meta($id, self::GENERATED, 1);
        update_post_meta($id, self::SOURCE, in_array($source, self::SOURCES, true) ? $source : 'manual');
    }

    public static function unmark(int $id): void
    {
        delete_post_meta($id, self::GENERATED);
        delete_post_meta($id, self::SOURCE);
    }

    public static function setPosition(int $id, ?Position $position): void
    {
        $position === null
            ? delete_post_meta($id, self::POSITION)
            : update_post_meta($id, self::POSITION, $position->value);
    }
}
