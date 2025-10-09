<?php
if (! function_exists('__')) {
    function __($text) {
        return $text;
    }
}

if (! function_exists('esc_html_e')) {
    function esc_html_e($text) {
        echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (! function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (! function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'https://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (! function_exists('wp_style_is')) {
    function wp_style_is() {
        return false;
    }
}

if (! function_exists('wp_register_style')) {
    function wp_register_style() {}
}

if (! function_exists('wp_add_inline_style')) {
    function wp_add_inline_style() {}
}

if (! function_exists('wp_script_is')) {
    function wp_script_is() {
        return false;
    }
}

if (! function_exists('wp_register_script')) {
    function wp_register_script() {}
}

if (! function_exists('wp_add_inline_script')) {
    function wp_add_inline_script() {}
}

if (! function_exists('wp_localize_script')) {
    function wp_localize_script() {}
}

if (! function_exists('shortcode_atts')) {
    function shortcode_atts($defaults, $atts) {
        return array_merge($defaults, $atts);
    }
}

if (! function_exists('wp_enqueue_style')) {
    function wp_enqueue_style() {}
}

if (! function_exists('wp_enqueue_script')) {
    function wp_enqueue_script() {}
}

if (! function_exists('wp_nonce_field')) {
    function wp_nonce_field() {}
}

if (! function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 0) {
        return random_int($min, $max);
    }
}

if (! function_exists('selected')) {
    function selected($value, $current) {
        return $value === $current ? ' selected' : '';
    }
}

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($value) {
        $value = is_string($value) ? $value : (string) $value;
        return trim(filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    }
}

if (! function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value) {
        return sanitize_text_field($value);
    }
}

if (! function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (! function_exists('is_email')) {
    function is_email($email) {
        return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (! function_exists('wp_unslash')) {
    function wp_unslash($value) {
        return $value;
    }
}

if (! function_exists('wp_list_pluck')) {
    function wp_list_pluck($input, $field) {
        $values = [];
        foreach ($input as $item) {
            if (isset($item[$field])) {
                $values[] = $item[$field];
            }
        }
        return $values;
    }
}

if (! function_exists('add_shortcode')) {
    function add_shortcode() {}
}

if (! function_exists('add_action')) {
    function add_action() {}
}

if (! function_exists('wp_verify_nonce')) {
    function wp_verify_nonce() {
        return true;
    }
}

if (! function_exists('wp_send_json')) {
    function wp_send_json($data) {
        echo json_encode($data);
    }
}
