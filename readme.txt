=== AI Image Disclosure ===
Contributors: zirkeldesign, d.sturm
Tags: ai, ai-generated, eu ai act, images, label
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Label AI-generated images with a small, accessible badge. CSS only, and it recognises AI images from their IPTC or C2PA metadata on upload.

== Description ==

**AI Image Disclosure** shows a small "AI-generated" label on images you mark in the media library, wherever they appear in a block.

= Built differently =

* **CSS only, no JavaScript.** The label sits inside the frame of the image, positioned with CSS. It never needs recalculating on scroll or resize, never jumps, and never floats over your sticky header or menus.
* **Recognises AI images on upload.** Images from generators that declare their origin, as an IPTC Digital Source Type or in Content Credentials (C2PA), are ticked automatically. A camera photo with Content Credentials is not.
* **Accessible.** The label is added to the alternative text, so screen readers announce it once. The visible badge is hidden from them to avoid saying it twice. Decorative images with an empty alternative text stay decorative.
* **Your content stays untouched.** Only the page output changes. Nothing is written into your posts or your image files, and the block editor never reports an invalid block.

= Features =

* "AI-generated" checkbox on every image, in the media modal and on the edit screen
* A position per image: top right, top left, bottom right, bottom left, or "Default"
* Default position for the whole site: change it once and every image left on "Default" follows
* Your own label text, one for the whole site, so the disclosure stays consistent
* Column in the media list view and bulk actions to mark or unmark many images at once
* Works with the Image, Cover, Media & Text and Post Featured Image blocks, including images in galleries
* Optional: tick uploads whose file name contains "ai" as a word of its own
* Restyle the label with CSS custom properties
* German translation included, informal and formal

= Restyling =

The label reads a few custom properties, which you can set in your theme:

`.has-aiid-badge { --aiid-bg: #000; --aiid-color: #fff; --aiid-font-size: .875rem; --aiid-radius: 0; --aiid-gap: 1rem; }`

== Installation ==

1. Install and activate the plugin.
2. Open an AI-generated image in the media library and tick "AI-generated".
3. Optionally set the label text and default position under Settings > Media.

== Frequently Asked Questions ==

= Does this make my site compliant with the EU AI Act? =

No plugin can promise that. The plugin helps you disclose AI-generated images clearly and accessibly. Whether that is enough depends on your content and your role. If you need certainty, take legal advice.

= Which images get the label? =

Every image marked as AI-generated, wherever it is shown in an Image, Cover, Media & Text or Post Featured Image block. Images your theme prints outside of blocks, and backgrounds of page builders, are not labelled.

= Why was an image ticked on upload? =

The file declared itself as AI-generated in its metadata. The image's edit screen says where the marking came from. You can untick it at any time.

= I marked an image but see no label. =

Clear your page cache first. Then check that the image is shown in one of the supported blocks.

= Does the plugin send data anywhere? =

No. It does not connect to any external service.

== Screenshots ==

1. The label on a cover block.
2. The checkbox and the position on the image in the media library.
3. The settings under Settings > Media.

== Changelog ==

= 1.0.0 =
* Added: Label for AI-generated images in Image, Cover, Media & Text and Post Featured Image blocks.
* Added: Automatic marking on upload for images with IPTC or C2PA metadata stating AI generation.
* Added: Position per image and a site-wide default, label text, media library column and bulk actions.
* Added: German translation, informal and formal.
