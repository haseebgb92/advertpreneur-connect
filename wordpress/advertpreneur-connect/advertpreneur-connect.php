<?php
/**
 * Plugin Name: Advertpreneur Connect
 * Plugin URI: https://advertpreneur.com/
 * Description: Secure WordPress bridge for trusted AI clients. Provides controlled content, media, quality-attestation, and publishing endpoints. It does not generate content.
 * Version: 0.7.0
 * Author: Advertpreneur
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: advertpreneur-connect
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ADPC_VERSION', '0.7.0');
define('ADPC_FILE', __FILE__);
define('ADPC_DIR', plugin_dir_path(__FILE__));
define('ADPC_URL', plugin_dir_url(__FILE__));

require_once ADPC_DIR . 'includes/class-adpc-quality.php';
require_once ADPC_DIR . 'includes/class-adpc-seo.php';
require_once ADPC_DIR . 'includes/class-adpc-rest.php';
require_once ADPC_DIR . 'includes/class-adpc-admin.php';

register_activation_hook(__FILE__, function () {
    add_option('adpc_installed_at', current_time('mysql', true));
    add_option('adpc_version', ADPC_VERSION);
});

add_action('plugins_loaded', function () {
    ADPC_REST::init();
    ADPC_Admin::init();
});
