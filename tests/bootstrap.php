<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/vendor/autoload.php';

/*
 * Minimal WordPress stubs for the parts of the plugin that are pure logic
 * (detection, positions, settings). Rendering is checked in a real install.
 */

if (! function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (! function_exists('get_option')) {
    function get_option(string $name, mixed $default = false): mixed
    {
        return $GLOBALS['__aiid_options'][$name] ?? $default;
    }
}

if (! function_exists('update_option')) {
    function update_option(string $name, mixed $value): bool
    {
        $GLOBALS['__aiid_options'][$name] = $value;

        return true;
    }
}

function aiid_reset_options(): void
{
    $GLOBALS['__aiid_options'] = [];
}
