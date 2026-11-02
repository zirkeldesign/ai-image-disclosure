<?php

declare(strict_types=1);

namespace ZirkelDesign\AiImageDisclosure\Detection;

/**
 * Reads whether an image file declares itself as AI-generated.
 *
 * Both standards name the same IPTC Digital Source Type, as a URI ending in
 * `trainedAlgorithmicMedia` (fully generated) or
 * `compositeWithTrainedAlgorithmicMedia` (partly generated). IPTC carries it
 * in the XMP packet, C2PA inside a JUMBF box labelled `c2pa`. Plain
 * `algorithmicMedia` (computed without a trained model, e.g. a fractal) is
 * not AI in the sense of the EU AI Act and is ignored, as is a C2PA manifest
 * that only records a camera capture.
 *
 * Pure string work on the file's bytes: no image library, no external tool.
 */
final class Provenance
{
    private const AI_SOURCE_TYPES = [
        'digitalsourcetype/trainedAlgorithmicMedia',
        'digitalsourcetype/compositeWithTrainedAlgorithmicMedia',
    ];

    /** Bytes read from the start and from the end of a file. */
    private const HEAD = 2_097_152;

    private const TAIL = 262_144;

    /**
     * @return 'iptc'|'c2pa'|null
     */
    public static function detect(string $bytes): ?string
    {
        foreach (self::xmpPackets($bytes) as $packet) {
            if (self::namesAiSourceType($packet)) {
                return 'iptc';
            }
        }

        if (self::hasC2paManifest($bytes) && self::namesAiSourceType($bytes)) {
            return 'c2pa';
        }

        return null;
    }

    /**
     * @return 'iptc'|'c2pa'|null
     */
    public static function detectFile(string $path): ?string
    {
        if (! is_readable($path)) {
            return null;
        }

        $size = (int) filesize($path);
        $bytes = (string) file_get_contents($path, false, null, 0, self::HEAD);

        if ($size > self::HEAD) {
            $bytes .= (string) file_get_contents($path, false, null, max(self::HEAD, $size - self::TAIL), self::TAIL);
        }

        return self::detect($bytes);
    }

    /**
     * "ai" or "ki" (German) as a word of its own: "hero-ai.jpg", "ki_bild.webp",
     * but not "kiel.jpg" or "aikido.png".
     */
    public static function filenameSuggestsAi(string $path): bool
    {
        return (bool) preg_match('/(^|[-_.\s])(ai|ki)([-_.\s]|$)/i', pathinfo($path, PATHINFO_FILENAME));
    }

    private static function namesAiSourceType(string $haystack): bool
    {
        foreach (self::AI_SOURCE_TYPES as $type) {
            if (str_contains($haystack, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function xmpPackets(string $bytes): array
    {
        preg_match_all('#<x:xmpmeta\b.*?</x:xmpmeta>#s', $bytes, $matches);

        return $matches[0];
    }

    /**
     * A JUMBF superbox ("jumb") whose description box ("jumd") carries the
     * label "c2pa". Present in JPEG APP11, the PNG caBX chunk and the WebP
     * C2PA chunk alike, so the container does not need to be parsed.
     */
    private static function hasC2paManifest(string $bytes): bool
    {
        $at = strpos($bytes, 'jumd');

        while ($at !== false) {
            if (str_contains(substr($bytes, $at, 64), 'c2pa')) {
                return true;
            }

            $at = strpos($bytes, 'jumd', $at + 4);
        }

        return false;
    }
}
