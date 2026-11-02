<?php

/**
 * Assertions for bin/integration-check.sh, run with `wp eval-file`.
 * No strict_types declaration: wp eval-file evals the file, where it may not come first.
 */

use ZirkelDesign\AiImageDisclosure\Attachment\Fields;
use ZirkelDesign\AiImageDisclosure\Attachment\Flag;
use ZirkelDesign\AiImageDisclosure\Attachment\Library;

require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';

$failures = 0;

$check = static function (string $what, bool $ok) use (&$failures): void {
    echo ($ok ? '  ✓ ' : '  ✗ ').$what.PHP_EOL;
    $failures += $ok ? 0 : 1;
};

/** A real JPEG, optionally with an XMP packet in APP1 right after SOI. */
$jpeg = static function (string $name, ?string $sourceType = null): string {
    $image = imagecreatetruecolor(40, 30);
    ob_start();
    imagejpeg($image);
    $bytes = (string) ob_get_clean();

    if ($sourceType !== null) {
        $xmp = '<x:xmpmeta xmlns:x="adobe:ns:meta/"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description xmlns:Iptc4xmpExt="http://iptc.org/std/Iptc4xmpExt/2008-02-29/"'
            .' Iptc4xmpExt:DigitalSourceType="http://cv.iptc.org/newscodes/digitalsourcetype/'.$sourceType.'"/></rdf:RDF></x:xmpmeta>';
        $body = "http://ns.adobe.com/xap/1.0/\0".$xmp;
        $bytes = substr($bytes, 0, 2)."\xFF\xE1".pack('n', strlen($body) + 2).$body.substr($bytes, 2);
    }

    $path = sys_get_temp_dir().'/'.$name;
    file_put_contents($path, $bytes);

    return $path;
};

$import = static function (string $path): int {
    $id = media_handle_sideload(['name' => basename($path), 'tmp_name' => $path], 0);

    return is_wp_error($id) ? 0 : (int) $id;
};

echo 'Detection on upload'.PHP_EOL;
$ai = $import($jpeg('generated.jpg', 'trainedAlgorithmicMedia'));
$camera = $import($jpeg('camera.jpg', 'digitalCapture'));
$named = $import($jpeg('hero-ai.jpg'));
$check('IPTC trainedAlgorithmicMedia is marked, source iptc', Flag::isGenerated($ai) && Flag::source($ai) === 'iptc');
$check('IPTC digitalCapture stays unmarked', ! Flag::isGenerated($camera));
$check('file name rule is off by default', ! Flag::isGenerated($named));

update_option('aiid_filename', '1');
$named2 = $import($jpeg('portrait-ai.jpg'));
$check('file name rule marks "portrait-ai.jpg" once switched on', Flag::isGenerated($named2) && Flag::source($named2) === 'filename');

echo 'Fields and bulk action'.PHP_EOL;
$fields = apply_filters('attachment_fields_to_edit', [], get_post($camera));
$check('edit screen shows checkbox and position', isset($fields['aiid_generated'], $fields['aiid_position']));
(new Fields)->save(['ID' => $camera], ['aiid_generated' => '1', 'aiid_position' => 'bottom-left']);
$check('saving the checkbox marks the image by hand with its corner', Flag::isGenerated($camera) && Flag::source($camera) === 'manual' && Flag::position($camera)->value === 'bottom-left');
(new Fields)->save(['ID' => $ai], ['aiid_generated' => '1', 'aiid_position' => '']);
$check('a detected source survives saving, "Default" stores no corner', Flag::source($ai) === 'iptc' && Flag::storedPosition($ai) === null);
(new Fields)->save(['ID' => $camera], ['aiid_position' => '']);
$check('saving without the checkbox removes the marking', ! Flag::isGenerated($camera));
$plain = $import($jpeg('plain.jpg'));
(new Library)->handle('upload.php', 'aiid_mark', [$plain]);
$check('bulk action marks', Flag::isGenerated($plain));
(new Library)->handle('upload.php', 'aiid_unmark', [$plain]);
$check('bulk action removes', ! Flag::isGenerated($plain));

echo 'Rendered output'.PHP_EOL;
$url = (string) wp_get_attachment_url($ai);
$render = static fn (string $markup): string => do_blocks($markup);

$image = $render("<!-- wp:image {\"id\":{$ai}} --><figure class=\"wp-block-image\"><img src=\"{$url}\" alt=\"A portrait\" class=\"wp-image-{$ai}\"/></figure><!-- /wp:image -->");
$check('image: frame classes with the default corner', str_contains($image, 'has-aiid-badge') && str_contains($image, 'aiid--top-right'));
$check('image: aria-hidden badge is the first child of the figure', (bool) preg_match('#<figure class="[^"]*has-aiid-badge[^"]*"><span class="aiid-badge" aria-hidden="true">AI-generated</span><img#', $image));
$check('image: no caption, no grid class', ! str_contains($image, 'aiid--captioned'));

$linked = $render("<!-- wp:image {\"id\":{$ai},\"linkDestination\":\"custom\"} --><figure class=\"wp-block-image\"><a href=\"https://example.com\"><img src=\"{$url}\" alt=\"L\" class=\"wp-image-{$ai}\"/></a><figcaption class=\"wp-element-caption\">Cap</figcaption></figure><!-- /wp:image -->");
$check('linked image with caption: badge beside the link, not inside it, grid class set', str_contains($linked, '<span class="aiid-badge" aria-hidden="true">AI-generated</span><a href') && str_contains($linked, 'aiid--captioned'));
$check('image: alt text carries the label', str_contains($image, 'alt="A portrait (AI-generated)"'));

$cover = $render("<!-- wp:cover {\"url\":\"{$url}\",\"id\":{$ai},\"dimRatio\":0} --><div class=\"wp-block-cover\"><img class=\"wp-block-cover__image-background wp-image-{$ai}\" alt=\"\" src=\"{$url}\" data-object-fit=\"cover\"/><span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim-0 has-background-dim\"></span><div class=\"wp-block-cover__inner-container\"><!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->");
$check('cover: badge inside the cover', (bool) preg_match('#class="wp-block-cover has-aiid-badge aiid--top-right"><span class="aiid-badge"#', $cover));
$check('cover: empty alt stays empty', str_contains($cover, 'alt=""'));

$media = $render("<!-- wp:media-text {\"mediaId\":{$ai},\"mediaType\":\"image\",\"mediaPosition\":\"right\"} --><div class=\"wp-block-media-text has-media-on-the-right\"><div class=\"wp-block-media-text__content\"><!-- wp:paragraph --><p>Text</p><!-- /wp:paragraph --></div><figure class=\"wp-block-media-text__media\"><img src=\"{$url}\" alt=\"MT\" class=\"wp-image-{$ai} size-full\"/></figure></div><!-- /wp:media-text -->");
$check('media & text: classes on the media figure, not the block', str_contains($media, 'wp-block-media-text__media has-aiid-badge') && ! str_contains($media, 'has-media-on-the-right has-aiid-badge'));
$check('media & text: badge first in the media figure, alt extended', (bool) preg_match('#__media has-aiid-badge[^"]*"><span class="aiid-badge"[^>]*>AI-generated</span><img[^>]*alt="MT \(AI-generated\)"#', $media));

update_post_meta($ai, '_aiid_position', 'bottom-right');
$check('a corner on the image wins over the default', str_contains($render("<!-- wp:image {\"id\":{$ai}} --><figure class=\"wp-block-image\"><img src=\"{$url}\" alt=\"\" class=\"wp-image-{$ai}\"/></figure><!-- /wp:image -->"), 'aiid--bottom-right'));
delete_post_meta($ai, '_aiid_position');
update_option('aiid_position', 'bottom-left');
update_option('aiid_label', 'Made with AI');
$moved = $render("<!-- wp:image {\"id\":{$ai}} --><figure class=\"wp-block-image\"><img src=\"{$url}\" alt=\"\" class=\"wp-image-{$ai}\"/></figure><!-- /wp:image -->");
$check('changing the default moves images left on "Default", label follows the setting', str_contains($moved, 'aiid--bottom-left') && str_contains($moved, '>Made with AI<'));
delete_option('aiid_position');
delete_option('aiid_label');

$post = wp_insert_post(['post_title' => 'Featured', 'post_status' => 'publish']);
set_post_thumbnail($post, $ai);
$block = new WP_Block(['blockName' => 'core/post-featured-image', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []], ['postId' => $post]);
$featured = $block->render();
$check('featured image block: badge and classes', str_contains($featured, 'has-aiid-badge') && str_contains($featured, '<span class="aiid-badge"'));

$unmarked = $render("<!-- wp:image {\"id\":{$plain}} --><figure class=\"wp-block-image\"><img src=\"x\" alt=\"Plain\" class=\"wp-image-{$plain}\"/></figure><!-- /wp:image -->");
$check('an unmarked image is left alone', ! str_contains($unmarked, 'aiid') && str_contains($unmarked, 'alt="Plain"'));

update_option('aiid_alt', '0');
$check('alt text option off leaves the alt alone', str_contains($render("<!-- wp:image {\"id\":{$ai}} --><figure class=\"wp-block-image\"><img src=\"{$url}\" alt=\"A portrait\" class=\"wp-image-{$ai}\"/></figure><!-- /wp:image -->"), 'alt="A portrait"'));

$saved = get_post_field('post_content', wp_insert_post(['post_content' => "<!-- wp:image {\"id\":{$ai}} --><figure class=\"wp-block-image\"><img src=\"{$url}\" alt=\"\" class=\"wp-image-{$ai}\"/></figure><!-- /wp:image -->", 'post_status' => 'publish']));
$check('saved content is not touched', ! str_contains($saved, 'aiid'));

echo PHP_EOL.($failures === 0 ? '✅ All checks passed' : "❌ {$failures} check(s) failed").PHP_EOL;
exit($failures === 0 ? 0 : 1);
