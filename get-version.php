<?php

/**
 * Extract the plugin version from the main file header for build scripts.
 */
$content = file_get_contents(__DIR__.'/ai-image-disclosure.php');
if (preg_match('/Version:\s*([0-9.]+)/', $content, $matches)) {
    echo $matches[1];
} else {
    echo '0.0.0';
}
