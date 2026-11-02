<?php

/**
 * Remove the plugin's options and the markings on images.
 *
 * Deleting the plugin is a deliberate act, so the markings go with it. Keep
 * an export of the marked images first if they are needed as a record.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

foreach (['aiid_label', 'aiid_position', 'aiid_detect', 'aiid_filename', 'aiid_alt'] as $aiid_option) {
    delete_option($aiid_option);
}

foreach (['_aiid_generated', '_aiid_position', '_aiid_source'] as $aiid_key) {
    delete_metadata('post', 0, $aiid_key, '', true);
}
