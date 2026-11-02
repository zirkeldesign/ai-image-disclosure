<?php

declare(strict_types=1);

use ZirkelDesign\AiImageDisclosure\Detection\Provenance;

/*
 * Fixtures are built in code: the parser works on bytes and does not care
 * whether the pixel data around the metadata is real.
 */

function xmp(string $sourceType): string
{
    return '<x:xmpmeta xmlns:x="adobe:ns:meta/"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
        .'<rdf:Description xmlns:Iptc4xmpExt="http://iptc.org/std/Iptc4xmpExt/2008-02-29/"'
        .' Iptc4xmpExt:DigitalSourceType="http://cv.iptc.org/newscodes/digitalsourcetype/'.$sourceType.'"/>'
        .'</rdf:RDF></x:xmpmeta>';
}

function jpegWithApp1(string $payload): string
{
    $body = "http://ns.adobe.com/xap/1.0/\0".$payload;

    return "\xFF\xD8\xFF\xE1".pack('n', strlen($body) + 2).$body."\xFF\xDA".str_repeat("\x00", 64)."\xFF\xD9";
}

function c2pa(string $assertion): string
{
    // jumb superbox, jumd description box labelled "c2pa", then manifest content.
    $jumd = 'jumd'.str_repeat("\x00", 16)."\x03c2pa\0";

    return pack('N', 8 + strlen($jumd) + strlen($assertion)).'jumb'.pack('N', strlen($jumd) + 4).$jumd.$assertion;
}

function pngWithCabx(string $box): string
{
    return "\x89PNG\r\n\x1a\n".pack('N', strlen($box)).'caBX'.$box."\0\0\0\0".str_repeat("\x00", 32).'IEND';
}

it('reads a fully AI-generated image from IPTC in XMP', function (): void {
    expect(Provenance::detect(jpegWithApp1(xmp('trainedAlgorithmicMedia'))))->toBe('iptc');
});

it('reads a partly AI-generated composite from IPTC', function (): void {
    expect(Provenance::detect(jpegWithApp1(xmp('compositeWithTrainedAlgorithmicMedia'))))->toBe('iptc');
});

it('ignores algorithmic media made without a trained model', function (): void {
    expect(Provenance::detect(jpegWithApp1(xmp('algorithmicMedia'))))->toBeNull();
});

it('ignores a camera capture declared in IPTC', function (): void {
    expect(Provenance::detect(jpegWithApp1(xmp('digitalCapture'))))->toBeNull();
});

it('reads a C2PA manifest stating AI generation', function (): void {
    $assertion = 'c2pa.actions{"action":"c2pa.created","digitalSourceType":"http://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia"}';

    expect(Provenance::detect(pngWithCabx(c2pa($assertion))))->toBe('c2pa');
});

it('ignores a C2PA manifest that only records a camera capture', function (): void {
    $assertion = 'c2pa.actions{"action":"c2pa.created","digitalSourceType":"http://cv.iptc.org/newscodes/digitalsourcetype/digitalCapture"}';

    expect(Provenance::detect(pngWithCabx(c2pa($assertion))))->toBeNull();
});

it('does not take an AI source type outside XMP and outside C2PA as evidence', function (): void {
    $png = pngWithCabx('tEXtcomment: see digitalsourcetype/trainedAlgorithmicMedia');

    expect(Provenance::detect($png))->toBeNull();
});

it('returns nothing for an image without metadata', function (): void {
    expect(Provenance::detect("\xFF\xD8".str_repeat("\x00", 512)."\xFF\xD9"))->toBeNull();
});

it('reads a file from disk', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'aiid');
    file_put_contents($file, jpegWithApp1(xmp('trainedAlgorithmicMedia')));

    expect(Provenance::detectFile($file))->toBe('iptc');

    unlink($file);
});

it('finds metadata at the end of a large file', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'aiid');
    file_put_contents($file, "\xFF\xD8".str_repeat("\x00", 3_000_000).jpegWithApp1(xmp('trainedAlgorithmicMedia')));

    expect(Provenance::detectFile($file))->toBe('iptc');

    unlink($file);
});

it('treats an unreadable path as no evidence', function (): void {
    expect(Provenance::detectFile('/does/not/exist.jpg'))->toBeNull();
});

it('suggests AI from a file name only for "ai" or "ki" as a word of its own', function (string $name, bool $expected): void {
    expect(Provenance::filenameSuggestsAi("/uploads/{$name}"))->toBe($expected);
})->with([
    ['hero-ai.jpg', true],
    ['ai_portrait.webp', true],
    ['ki-bild.png', true],
    ['Header KI.png', true],
    ['kiel.jpg', false],
    ['aikido.jpg', false],
    ['mai-2026.jpg', false],
    ['detail.jpg', false],
]);
