<?php
if (!defined('ABSPATH')) exit;

class ADPC_Admin {
    public static function init() {
        add_action('admin_menu', [self::class, 'menu']);
    }

    public static function menu() {
        add_menu_page(
            'Advertpreneur Connect',
            'Advertpreneur Connect',
            'manage_options',
            'advertpreneur-connect',
            [self::class, 'page'],
            'dashicons-admin-links',
            65
        );
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;

        $rest = esc_url(rest_url('advertpreneur-connect/v1/status'));
        $app_passwords = function_exists('wp_is_application_passwords_available') && wp_is_application_passwords_available();
        $service = apply_filters('adpc_service_url', 'https://advertpreneur-connect.vercel.app');
        $connect_url = add_query_arg('site_url', home_url('/'), trailingslashit($service) . 'connect');
        $connected = isset($_GET['adpc_connected']) && '1' === sanitize_text_field(wp_unslash($_GET['adpc_connected']));
        $seo = ADPC_SEO::detect();
        $seo_label = !empty($seo['conflict']) ? 'Multiple SEO plugins detected (resolve conflict)' : (!empty($seo['primary']['name']) ? $seo['primary']['name'] : 'No supported SEO plugin detected');
        ?>
        <div class="wrap" style="max-width:980px">
            <h1 style="margin-bottom:8px">Advertpreneur Connect</h1>
            <p style="font-size:15px;max-width:760px">Secure bridge between this WordPress site and trusted MCP clients. The plugin itself never generates articles or images.</p>

            <?php if ($connected): ?>
                <div class="notice notice-success is-dismissible"><p><strong>Connected successfully.</strong> WordPress created a revocable application credential for Advertpreneur Connect.</p></div>
            <?php endif; ?>

            <div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:24px;margin:24px 0;box-shadow:0 1px 2px rgba(0,0,0,.04)">
                <h2 style="margin-top:0">Connect this site</h2>
                <p>One click opens WordPress's own authorization screen. Your normal WordPress password is never shared.</p>
                <?php if ($app_passwords): ?>
                    <p><a class="button button-primary button-hero" href="<?php echo esc_url($connect_url); ?>">Connect to Advertpreneur</a></p>
                    <p class="description">You can revoke the connection later under Users → Profile → Application Passwords.</p>
                <?php else: ?>
                    <p><button class="button button-primary button-hero" disabled>Connect to Advertpreneur</button></p>
                    <p style="color:#b32d2e"><strong>Application Passwords are unavailable on this WordPress installation.</strong></p>
                <?php endif; ?>
            </div>

            <table class="widefat striped" style="max-width:980px">
                <tbody>
                    <tr><td style="width:220px"><strong>Version</strong></td><td><?php echo esc_html(ADPC_VERSION); ?></td></tr>
                    <tr><td><strong>REST endpoint</strong></td><td><code><?php echo $rest; ?></code></td></tr>
                    <tr><td><strong>Application Passwords</strong></td><td><?php echo $app_passwords ? 'Available' : 'Unavailable / disabled'; ?></td></tr>
                    <tr><td><strong>Image upload modes</strong></td><td>Base64 bytes, HTTPS import, and native WordPress REST multipart. Images are planned against the destination slot and validated before placement.</td></tr>
                    <tr><td><strong>Content generation</strong></td><td>None. Generation/research happens outside WordPress; this plugin only exposes controlled site actions.</td></tr>
                    <tr><td><strong>Managed content</strong></td><td>Posts, pages, media, and WooCommerce products when WooCommerce is active.</td></tr>
                    <tr><td><strong>SEO integration</strong></td><td><?php echo esc_html($seo_label); ?>. Advertpreneur Connect uses a generic SEO layer and writes to the active supported plugin.</td></tr>
                    <tr><td><strong>Taxonomy & internal links</strong></td><td>Categories/tags can be discovered before assignment; internal-link candidates come from real published site content.</td></tr>
                    <tr><td><strong>Publish safety</strong></td><td>The Advertpreneur publish endpoint requires a passing quality attestation tied to the current content hash.</td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}
