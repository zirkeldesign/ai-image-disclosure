<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure;

enum Position: string
{
    case TopRight = 'top-right';
    case TopLeft = 'top-left';
    case BottomRight = 'bottom-right';
    case BottomLeft = 'bottom-left';

    public function label(): string
    {
        return match ($this) {
            self::TopRight => __('Top right', 'ai-image-disclosure'),
            self::TopLeft => __('Top left', 'ai-image-disclosure'),
            self::BottomRight => __('Bottom right', 'ai-image-disclosure'),
            self::BottomLeft => __('Bottom left', 'ai-image-disclosure'),
        };
    }
}
