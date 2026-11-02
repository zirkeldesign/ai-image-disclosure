<?php

declare(strict_types=1);

use ZirkelDesign\AiImageDisclosure\Position;
use ZirkelDesign\AiImageDisclosure\Settings;

it('falls back to the translated default label', function (): void {
    expect(Settings::label())->toBe('AI-generated');

    update_option(Settings::LABEL, '   ');
    expect(Settings::label())->toBe('AI-generated');

    update_option(Settings::LABEL, 'Made with AI');
    expect(Settings::label())->toBe('Made with AI');
});

it('defaults to the top right corner and ignores unknown values', function (): void {
    expect(Settings::defaultPosition())->toBe(Position::TopRight);

    update_option(Settings::POSITION, 'middle');
    expect(Settings::defaultPosition())->toBe(Position::TopRight);

    update_option(Settings::POSITION, 'bottom-left');
    expect(Settings::defaultPosition())->toBe(Position::BottomLeft);
});

it('detects on upload and appends to the alt text by default, but leaves the file name rule off', function (): void {
    expect(Settings::detectOnUpload())->toBeTrue()
        ->and(Settings::appendToAlt())->toBeTrue()
        ->and(Settings::detectByFilename())->toBeFalse();
});

it('keeps a toggle that was switched off', function (): void {
    update_option(Settings::ALT, '0');
    update_option(Settings::FILENAME, '1');

    expect(Settings::appendToAlt())->toBeFalse()
        ->and(Settings::detectByFilename())->toBeTrue();
});
