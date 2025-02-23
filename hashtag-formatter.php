<?php
/**
 * Plugin Name: Hashtag Formatter
 * Plugin URI: https://devapps.uk
 * Description: Converts a comma-separated list of tags into formatted hashtags for social media.
 * Version: 0.0.10-dev
 * Author: Tamjid Bhuiyan
 * Author URI: https://devapps.uk
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add settings menu for Hashtag Formatter
function hashtag_formatter_menu() {
    add_options_page(
        'Hashtag Formatter Settings',
        'Hashtag Formatter',
        'manage_options',
        'hashtag-formatter',
        'hashtag_formatter_settings_page'
    );
}
add_action('admin_menu', 'hashtag_formatter_menu');

function hashtag_formatter_settings_page() {
    ?>
    <div class="wrap">
        <h1>Hashtag Formatter Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('hashtag_formatter_options');
            do_settings_sections('hashtag-formatter');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function hashtag_formatter_settings_init() {
    add_option('hashtag_formatter_security_key', '');
    register_setting('hashtag_formatter_options', 'hashtag_formatter_security_key', 'sanitize_text_field');

    add_settings_section(
        'hashtag_formatter_section',
        'Security Settings',
        null,
        'hashtag-formatter'
    );

    add_settings_field(
        'hashtag_formatter_security_key',
        'Security Key',
        'hashtag_formatter_security_key_callback',
        'hashtag-formatter',
        'hashtag_formatter_section'
    );
}
add_action('admin_init', 'hashtag_formatter_settings_init');

function hashtag_formatter_security_key_callback() {
    echo '<input type="text" name="hashtag_formatter_security_key" value="' . esc_attr(get_option('hashtag_formatter_security_key')) . '" class="regular-text">';
}

function format_hashtags($tags) {
    $tagsArray = array_map('trim', explode(',', $tags));
    $hashtags = [];

    foreach ($tagsArray as $tag) {
        $cleanTag = preg_replace('/\s+/', '', $tag); // Remove all spaces
        $cleanTag = preg_replace('/[^a-zA-Z0-9\$\#]/', '', $cleanTag); // Remove special characters except # $
        $cleanTag = strtolower($cleanTag); // Convert to lowercase
        if (!empty($cleanTag)) { // Check if the tag is not empty after cleaning
            $hashtags[] = '#' . $cleanTag;
        }
    }

    $result = implode(' ', array_slice($hashtags, 0, 5)); // Join the first 5 hashtags with spaces
    return trim($result, '"'); // Remove surrounding double quotes, if any
}

function hashtag_formatter_api() {
    register_rest_route('hashtag-formatter/v1', '/convert/', array(
        'methods' => 'POST',
        'callback' => function(WP_REST_Request $request) {
            $tags = $request->get_param('tags');
            $provided_key = $request->get_param('security_key');
            $stored_key = get_option('hashtag_formatter_security_key');

            if (!$tags) {
                return new WP_REST_Response(['error' => 'No tags provided'], 400);
            }

            if (!$stored_key || $provided_key !== $stored_key) {
                return new WP_REST_Response(['error' => 'Unauthorized request'], 403);
            }

            $formatted = format_hashtags($tags);
            return new WP_REST_Response($formatted, 200);
        },
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'hashtag_formatter_api');