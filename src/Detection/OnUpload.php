<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure\Detection;

use ZirkelDesign\AiImageDisclosure\Attachment\Flag;
use ZirkelDesign\AiImageDisclosure\Settings;

/**
 * Ticks the checkbox on upload when the file says it was made by AI. After
 * that only the checkbox counts; nothing is re-checked later.
 */
final class OnUpload
{
    public function register(): void
    {
        add_action('add_attachment', [$this, 'inspect']);
    }

    public function inspect(int $id): void
    {
        if (! wp_attachment_is_image($id)) {
            return;
        }

        $file = (string) get_attached_file($id);
        $source = Settings::detectOnUpload() ? Provenance::detectFile($file) : null;

        if ($source === null && Settings::detectByFilename() && Provenance::filenameSuggestsAi($file)) {
            $source = 'filename';
        }

        if ($source !== null) {
            Flag::mark($id, $source);
        }
    }
}
