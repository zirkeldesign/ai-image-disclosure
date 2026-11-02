<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure;

use ZirkelDesign\AiImageDisclosure\Attachment\Fields;
use ZirkelDesign\AiImageDisclosure\Attachment\Library;
use ZirkelDesign\AiImageDisclosure\Detection\OnUpload;
use ZirkelDesign\AiImageDisclosure\Frontend\Badge;

final class Plugin
{
    public static function boot(): void
    {
        (new Settings)->register();
        (new Fields)->register();
        (new Library)->register();
        (new OnUpload)->register();
        (new Badge)->register();
    }
}
