<?php
if (!defined('ABSPATH')) exit;

/**
 * SEO-plugin abstraction for Advertpreneur Connect.
 *
 * The MCP layer uses one stable field vocabulary while this adapter reads/writes
 * the active SEO plugin's native storage/API. We intentionally avoid inventing
 * SEO output when no supported plugin is active.
 */
class ADPC_SEO {
    const SUPPORTED = ['aioseo', 'rank_math', 'yoast', 'seopress'];

    private static function active_plugin_files() {
        $active = (array) get_option('active_plugins', []);
        if (is_multisite()) {
            $network = (array) get_site_option('active_sitewide_plugins', []);
            $active = array_merge($active, array_keys($network));
        }
        return array_values(array_unique(array_map('strval', $active)));
    }

    private static function plugin_file_active($needles) {
        $active = self::active_plugin_files();
        foreach ((array) $needles as $needle) {
            foreach ($active as $file) {
                if ($file === $needle || strpos($file, $needle) !== false) return true;
            }
        }
        return false;
    }

    public static function detect() {
        $plugins = [];

        if (function_exists('aioseo') || defined('AIOSEO_VERSION') || self::plugin_file_active(['all-in-one-seo-pack/'])) {
            $plugins[] = [
                'id' => 'aioseo',
                'name' => 'All in One SEO (AIOSEO)',
                'version' => defined('AIOSEO_VERSION') ? (string) AIOSEO_VERSION : null,
                'supports' => [
                    'meta_title' => true,
                    'meta_description' => true,
                    'focus_keyword' => true,
                    'canonical_url' => true,
                ],
                'adapter' => 'aioseo_rest',
            ];
        }

        if (defined('RANK_MATH_VERSION') || class_exists('RankMath') || self::plugin_file_active(['seo-by-rank-math/'])) {
            $plugins[] = [
                'id' => 'rank_math',
                'name' => 'Rank Math SEO',
                'version' => defined('RANK_MATH_VERSION') ? (string) RANK_MATH_VERSION : null,
                'supports' => [
                    'meta_title' => true,
                    'meta_description' => true,
                    'focus_keyword' => true,
                    'canonical_url' => true,
                ],
                'adapter' => 'post_meta',
            ];
        }

        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Options') || self::plugin_file_active(['wordpress-seo/', 'wordpress-seo-premium/'])) {
            $plugins[] = [
                'id' => 'yoast',
                'name' => 'Yoast SEO',
                'version' => defined('WPSEO_VERSION') ? (string) WPSEO_VERSION : null,
                'supports' => [
                    'meta_title' => true,
                    'meta_description' => true,
                    'focus_keyword' => true,
                    'canonical_url' => true,
                ],
                'adapter' => 'post_meta',
            ];
        }

        if (defined('SEOPRESS_VERSION') || self::plugin_file_active(['wp-seopress/'])) {
            $plugins[] = [
                'id' => 'seopress',
                'name' => 'SEOPress',
                'version' => defined('SEOPRESS_VERSION') ? (string) SEOPRESS_VERSION : null,
                'supports' => [
                    'meta_title' => true,
                    'meta_description' => true,
                    'focus_keyword' => true,
                    'canonical_url' => true,
                ],
                'adapter' => 'post_meta',
            ];
        }

        // Remove accidental duplicate detections while preserving priority/order.
        $seen = [];
        $unique = [];
        foreach ($plugins as $plugin) {
            if (isset($seen[$plugin['id']])) continue;
            $seen[$plugin['id']] = true;
            $unique[] = $plugin;
        }

        $conflict = count($unique) > 1;
        $primary = count($unique) === 1 ? $unique[0] : null;
        return [
            'detected' => !empty($unique),
            'conflict' => $conflict,
            'primary' => $primary,
            'plugins' => $unique,
            'supported_plugins' => [
                ['id' => 'aioseo', 'name' => 'All in One SEO (AIOSEO)'],
                ['id' => 'rank_math', 'name' => 'Rank Math SEO'],
                ['id' => 'yoast', 'name' => 'Yoast SEO'],
                ['id' => 'seopress', 'name' => 'SEOPress'],
            ],
        ];
    }

    private static function primary_plugin() {
        $detected = self::detect();
        if (!empty($detected['conflict'])) {
            return new WP_Error(
                'adpc_seo_plugin_conflict',
                'More than one supported SEO plugin appears active. Disable the duplicate SEO plugin before Advertpreneur Connect writes SEO metadata.',
                ['status' => 409, 'seo' => $detected]
            );
        }
        if (empty($detected['primary'])) {
            return new WP_Error(
                'adpc_seo_plugin_missing',
                'No supported SEO plugin is active. Advertpreneur Connect will not invent SEO metadata storage that the frontend does not use.',
                ['status' => 422, 'seo' => $detected]
            );
        }
        return $detected['primary'];
    }

    private static function blank_fields() {
        return [
            'meta_title' => '',
            'meta_description' => '',
            'focus_keyword' => '',
            'canonical_url' => '',
        ];
    }

    private static function title_metrics($title) {
        $title = (string) $title;
        return [
            'characters' => function_exists('mb_strlen') ? mb_strlen($title) : strlen($title),
            'note' => 'Character count is only a review signal; search engines use rendered width and may rewrite titles.',
        ];
    }

    private static function description_metrics($description) {
        $description = (string) $description;
        return [
            'characters' => function_exists('mb_strlen') ? mb_strlen($description) : strlen($description),
            'note' => 'Character count is only a review signal; search engines may choose a different snippet.',
        ];
    }

    private static function rest_route_for_post($post) {
        if (function_exists('rest_get_route_for_post')) {
            $route = rest_get_route_for_post($post);
            if ($route) return $route;
        }
        $type = get_post_type_object($post->post_type);
        if (!$type || empty($type->show_in_rest)) return null;
        $namespace = !empty($type->rest_namespace) ? $type->rest_namespace : 'wp/v2';
        $base = !empty($type->rest_base) ? $type->rest_base : $type->name;
        return '/' . trim($namespace, '/') . '/' . trim($base, '/') . '/' . $post->ID;
    }

    private static function aioseo_meta_data($post) {
        $route = self::rest_route_for_post($post);
        if (!$route) return new WP_Error('adpc_aioseo_rest_unavailable', 'The post type does not expose a REST route required by AIOSEO.', ['status' => 422]);

        $request = new WP_REST_Request('GET', $route);
        $response = rest_do_request($request);
        if ($response->is_error()) return $response->as_error();
        $data = $response->get_data();
        if (!is_array($data) || !array_key_exists('aioseo_meta_data', $data)) {
            return new WP_Error('adpc_aioseo_meta_unavailable', 'AIOSEO is active but aioseo_meta_data was not exposed by the post REST endpoint.', ['status' => 422]);
        }
        $raw = $data['aioseo_meta_data'];
        if (is_object($raw)) $raw = (array) $raw;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $raw = $decoded;
        }
        return is_array($raw) ? $raw : [];
    }

    private static function decode_keyphrases($raw) {
        if (is_object($raw)) $raw = (array) $raw;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return [$decoded, 'json'];
            return [[], 'json'];
        }
        return [is_array($raw) ? $raw : [], 'array'];
    }

    private static function aioseo_focus_keyword($meta) {
        if (!is_array($meta) || !array_key_exists('keyphrases', $meta)) return '';
        list($kp) = self::decode_keyphrases($meta['keyphrases']);
        if (!empty($kp['focus']) && is_array($kp['focus']) && isset($kp['focus']['keyphrase'])) {
            return (string) $kp['focus']['keyphrase'];
        }
        return '';
    }

    private static function read_aioseo($post) {
        $meta = self::aioseo_meta_data($post);
        if (is_wp_error($meta)) return $meta;
        return [
            'meta_title' => isset($meta['title']) ? (string) $meta['title'] : '',
            'meta_description' => isset($meta['description']) ? (string) $meta['description'] : '',
            'focus_keyword' => self::aioseo_focus_keyword($meta),
            'canonical_url' => isset($meta['canonical_url']) ? (string) $meta['canonical_url'] : '',
            'raw_available' => array_keys($meta),
        ];
    }

    private static function write_aioseo($post, $fields) {
        $existing = self::aioseo_meta_data($post);
        if (is_wp_error($existing)) return $existing;
        $payload = [];

        if (array_key_exists('meta_title', $fields)) $payload['title'] = sanitize_text_field((string) $fields['meta_title']);
        if (array_key_exists('meta_description', $fields)) $payload['description'] = sanitize_textarea_field((string) $fields['meta_description']);
        if (array_key_exists('canonical_url', $fields)) $payload['canonical_url'] = esc_url_raw((string) $fields['canonical_url']);

        if (array_key_exists('focus_keyword', $fields)) {
            $raw = array_key_exists('keyphrases', $existing) ? $existing['keyphrases'] : [];
            list($kp, $encoding) = self::decode_keyphrases($raw);
            $focus = (!empty($kp['focus']) && is_array($kp['focus'])) ? $kp['focus'] : [];
            $focus['keyphrase'] = sanitize_text_field((string) $fields['focus_keyword']);
            $kp['focus'] = $focus;
            if (!isset($kp['additional']) || !is_array($kp['additional'])) $kp['additional'] = [];
            $payload['keyphrases'] = $encoding === 'json' ? wp_json_encode($kp) : $kp;
        }

        if (!$payload) return true;
        $route = self::rest_route_for_post($post);
        if (!$route) return new WP_Error('adpc_aioseo_rest_unavailable', 'The post type does not expose a REST route required by AIOSEO.', ['status' => 422]);

        $request = new WP_REST_Request('POST', $route);
        $request->set_param('aioseo_meta_data', $payload);
        $response = rest_do_request($request);
        if ($response->is_error()) return $response->as_error();
        return true;
    }

    private static function meta_map($plugin_id) {
        if ($plugin_id === 'yoast') {
            return [
                'meta_title' => '_yoast_wpseo_title',
                'meta_description' => '_yoast_wpseo_metadesc',
                'focus_keyword' => '_yoast_wpseo_focuskw',
                'canonical_url' => '_yoast_wpseo_canonical',
            ];
        }
        if ($plugin_id === 'rank_math') {
            return [
                'meta_title' => 'rank_math_title',
                'meta_description' => 'rank_math_description',
                'focus_keyword' => 'rank_math_focus_keyword',
                'canonical_url' => 'rank_math_canonical_url',
            ];
        }
        if ($plugin_id === 'seopress') {
            return [
                'meta_title' => '_seopress_titles_title',
                'meta_description' => '_seopress_titles_desc',
                'focus_keyword' => '_seopress_analysis_target_kw',
                'canonical_url' => '_seopress_robots_canonical',
            ];
        }
        return [];
    }

    private static function read_post_meta_adapter($post_id, $plugin_id) {
        $fields = self::blank_fields();
        foreach (self::meta_map($plugin_id) as $generic => $key) {
            $fields[$generic] = (string) get_post_meta($post_id, $key, true);
        }
        return $fields;
    }

    private static function write_post_meta_adapter($post_id, $plugin_id, $fields) {
        foreach (self::meta_map($plugin_id) as $generic => $key) {
            if (!array_key_exists($generic, $fields)) continue;
            $value = $generic === 'canonical_url'
                ? esc_url_raw((string) $fields[$generic])
                : ($generic === 'meta_description'
                    ? sanitize_textarea_field((string) $fields[$generic])
                    : sanitize_text_field((string) $fields[$generic]));
            if ($value === '') delete_post_meta($post_id, $key);
            else update_post_meta($post_id, $key, $value);
        }
        return true;
    }

    public static function read($post_id) {
        $post = get_post((int) $post_id);
        if (!$post) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        $plugin = self::primary_plugin();
        if (is_wp_error($plugin)) {
            return [
                'available' => false,
                'seo' => self::detect(),
                'fields' => self::blank_fields(),
                'error' => ['code' => $plugin->get_error_code(), 'message' => $plugin->get_error_message()],
            ];
        }

        if ($plugin['id'] === 'aioseo') $fields = self::read_aioseo($post);
        else $fields = self::read_post_meta_adapter($post->ID, $plugin['id']);
        if (is_wp_error($fields)) return $fields;

        return [
            'available' => true,
            'plugin' => $plugin,
            'fields' => $fields,
            'review' => [
                'title' => self::title_metrics(isset($fields['meta_title']) ? $fields['meta_title'] : ''),
                'description' => self::description_metrics(isset($fields['meta_description']) ? $fields['meta_description'] : ''),
            ],
        ];
    }

    public static function write($post_id, $fields) {
        $post = get_post((int) $post_id);
        if (!$post) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $post->ID)) return new WP_Error('adpc_forbidden', 'Cannot edit SEO data for this content.', ['status' => 403]);
        if (!is_array($fields)) return new WP_Error('adpc_invalid_seo_fields', 'SEO fields must be an object.', ['status' => 400]);

        $allowed = ['meta_title', 'meta_description', 'focus_keyword', 'canonical_url'];
        $clean = [];
        foreach ($allowed as $field) if (array_key_exists($field, $fields)) $clean[$field] = $fields[$field];
        if (!$clean) return new WP_Error('adpc_no_seo_fields', 'No supported SEO fields were supplied.', ['status' => 400]);

        $plugin = self::primary_plugin();
        if (is_wp_error($plugin)) return $plugin;
        $result = $plugin['id'] === 'aioseo'
            ? self::write_aioseo($post, $clean)
            : self::write_post_meta_adapter($post->ID, $plugin['id'], $clean);
        if (is_wp_error($result)) return $result;

        return self::read($post->ID);
    }
}
