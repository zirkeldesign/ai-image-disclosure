<?php

/**
 * Plugin Name:       AI Image Disclosure
 * Plugin URI:        https://github.com/zirkeldesign/ai-image-disclosure
 * Description:       Label AI-generated images with a small, accessible badge. CSS only, detects IPTC and C2PA provenance on upload.
 * Version:           1.0.0
 * Author:            zirkel.design
 * Author URI:        https://zirkel.design
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-image-disclosure
 * Domain Path:       /languages
 * Requires PHP:      8.3
 * Requires at least: 6.6
 */

declare(strict_types=1);

use ZirkelDesign\AiImageDisclosure\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

define('AI_IMAGE_DISCLOSURE_VERSION', '1.0.0');
define('AI_IMAGE_DISCLOSURE_FILE', __FILE__);
define('AI_IMAGE_DISCLOSURE_DIR', plugin_dir_path(__FILE__));
define('AI_IMAGE_DISCLOSURE_URL', plugin_dir_url(__FILE__));

if (file_exists(AI_IMAGE_DISCLOSURE_DIR.'vendor/autoload.php')) {
    require_once AI_IMAGE_DISCLOSURE_DIR.'vendor/autoload.php';
}

/*
 * load_plugin_textdomain() is intentionally not called: WordPress 4.6+ loads
 * `ai-image-disclosure-{locale}.mo` from /languages/ by itself.
 */
add_action('plugins_loaded', static function (): void {
    Plugin::boot();
});
