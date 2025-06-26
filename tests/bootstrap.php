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
require_once __DIR__ . '/../includes/class-cdc-utils.php';
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

// Minimal WordPress function stubs used by the plugin during tests.
function get_post_type($post = null) {
    return 'council';
}
function get_post_status($post = null) {
    return 'publish';
}
function get_post($id) {
    return (object) ['ID' => $id, 'post_type' => 'council'];
}
function get_permalink($id = 0) {
    return '';
}
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_register_style() {}
function wp_register_script() {}
function wp_add_inline_style() {}
function wp_localize_script() {}
function add_action() {}
function add_shortcode() {}
function esc_html($t){return $t;}
function esc_attr($t){return $t;}
function esc_html_e($t,$d=null){echo $t;}
function __($t,$d=null){return $t;}
function sanitize_key($t){return $t;}
function sanitize_text_field($t){return $t;}
function sanitize_html_class($t){return $t;}
function check_ajax_referer($a,$b=null,$die=true){}
function wp_send_json_success($data=null){return $data;}
function wp_send_json_error($data=null,$code=400){return $data;}
function wp_create_nonce($action){return 'nonce';}
function admin_url($path=''){return $path;}
// Simple storage for post meta used in tests.
$post_meta_store = [];
function get_post_meta($id, $key = '', $single = false){
    global $post_meta_store;
    if($key === ''){ return $post_meta_store[$id] ?? []; }
    $val = $post_meta_store[$id][$key] ?? null;
    if($single){ return $val; }
    return [$val];
}
function update_post_meta($id,$key,$value){
    global $post_meta_store;
    $post_meta_store[$id][$key] = $value;
    return true;
}
function delete_post_meta($id,$key){
    global $post_meta_store;
    unset($post_meta_store[$id][$key]);
    return true;
}
function number_format_i18n($number, $decimals = 0){
    return number_format($number, $decimals);
}
