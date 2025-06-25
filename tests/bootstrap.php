<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load WordPress stubs but remove serialization helpers so we can provide our own implementations.
$stubFile = __DIR__ . '/../vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
$code = file_get_contents($stubFile);
$code = preg_replace('/function\s+maybe_serialize\(.*?\n\}/s', '', $code);
$code = preg_replace('/function\s+maybe_unserialize\(.*?\n\}/s', '', $code);
$code = preg_replace('/function\s+is_serialized\(.*?\n\}/s', '', $code);
$code = preg_replace('/function\s+is_serialized_string\(.*?\n\}/s', '', $code);
if ($code !== false) {
    eval('?>' . $code);
}

require_once __DIR__ . '/WPDBStub.php';

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

global $wpdb;
$wpdb = new WPDBStub();

require_once __DIR__ . '/../includes/class-counter-manager.php';
require_once __DIR__ . '/../includes/class-custom-fields.php';
require_once __DIR__ . '/../includes/class-docs-manager.php';

function is_serialized($data, $strict = true) {
    if (!is_string($data)) return false;
    $data = trim($data);
    if ('N;' === $data) return true;
    if (strlen($data) < 4) return false;
    if ($data[1] !== ':') return false;
    if ($strict) {
        $last = substr($data, -1);
        if ($last !== ';' && $last !== '}') return false;
    } else {
        $semicolon = strpos($data, ';');
        $brace = strpos($data, '}');
        if ($semicolon === false && $brace === false) return false;
        if ($semicolon !== false && $semicolon < 3) return false;
        if ($brace !== false && $brace < 4) return false;
    }
    switch ($data[0]) {
        case 's':
            if ($strict && substr($data, -2) !== '";') return false;
            elseif (!($strict || strpos($data, ';') !== false || strpos($data, '}') !== false)) return false;
            break;
        case 'a':
        case 'O':
        case 'E':
            return (bool) preg_match('/^' . $data[0] . ':[0-9]+:/s', $data);
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match('/^' . $data[0] . ':[0-9.E-]+;'. $end .'/', $data);
    }
    return false;
}
function maybe_serialize($data) {
    if (is_array($data) || is_object($data)) return serialize($data);
    if (is_serialized($data, false)) return trim($data);
    return $data;
}
function maybe_unserialize($data) {
    if (is_serialized($data, false)) return @unserialize(trim($data));
    return $data;
}

$options_store = [];
function get_option($option, $default = false) {
    global $options_store;
    return $options_store[$option] ?? $default;
}
function update_option($option, $value) {
    global $options_store;
    $options_store[$option] = $value;
    return true;
}
