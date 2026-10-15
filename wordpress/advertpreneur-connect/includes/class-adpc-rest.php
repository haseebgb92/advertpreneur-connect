<?php
if (!defined('ABSPATH')) exit;

class ADPC_REST {
    const NS = 'advertpreneur-connect/v1';
    const META_QUALITY = '_adpc_quality_attestation';

    public static function init() {
        add_action('rest_api_init', [self::class, 'register_routes']);
        add_action('init', [self::class, 'register_meta']);
    }

    public static function register_meta() {
        register_post_meta('', self::META_QUALITY, [
            'type' => 'object',
            'single' => true,
            'show_in_rest' => false,
            'auth_callback' => function () { return current_user_can('edit_posts'); },
        ]);
    }

    public static function can_read() { return current_user_can('edit_posts'); }
    public static function can_edit() { return current_user_can('edit_posts'); }
    public static function can_upload() { return current_user_can('upload_files'); }
    public static function can_publish() { return current_user_can('publish_posts') || current_user_can('publish_pages'); }
    public static function can_products() { return current_user_can('edit_products') || current_user_can('manage_woocommerce') || current_user_can('manage_options'); }
    public static function can_publish_products() { return current_user_can('publish_products') || current_user_can('manage_woocommerce') || current_user_can('manage_options'); }

    public static function register_routes() {
        register_rest_route(self::NS, '/status', [
            'methods' => 'GET',
            'callback' => [self::class, 'status'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/content/search', [
            'methods' => 'GET',
            'callback' => [self::class, 'search_content'],
            'permission_callback' => [self::class, 'can_read'],
            'args' => ['q' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field']],
        ]);

        register_rest_route(self::NS, '/content/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_content'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/seo/status', [
            'methods' => 'GET',
            'callback' => [self::class, 'seo_status'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/seo/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'get_seo'],
                'permission_callback' => [self::class, 'can_read'],
            ],
            [
                'methods' => ['PATCH', 'POST'],
                'callback' => [self::class, 'update_seo'],
                'permission_callback' => [self::class, 'can_edit'],
            ],
        ]);

        register_rest_route(self::NS, '/seo/(?P<id>\d+)/audit', [
            'methods' => 'GET',
            'callback' => [self::class, 'seo_audit'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/terms', [
            'methods' => 'GET',
            'callback' => [self::class, 'list_terms'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/internal-links/suggest', [
            'methods' => 'POST',
            'callback' => [self::class, 'suggest_internal_links'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/drafts', [
            'methods' => 'POST',
            'callback' => [self::class, 'create_draft'],
            'permission_callback' => [self::class, 'can_edit'],
        ]);

        register_rest_route(self::NS, '/posts/(?P<id>\d+)', [
            'methods' => ['PATCH', 'POST'],
            'callback' => [self::class, 'update_post'],
            'permission_callback' => [self::class, 'can_edit'],
        ]);

        register_rest_route(self::NS, '/quality/check', [
            'methods' => 'POST',
            'callback' => [self::class, 'quality_check'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/quality/overlap', [
            'methods' => 'POST',
            'callback' => [self::class, 'quality_overlap'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/quality/attest', [
            'methods' => 'POST',
            'callback' => [self::class, 'quality_attest'],
            'permission_callback' => [self::class, 'can_edit'],
        ]);

        register_rest_route(self::NS, '/posts/(?P<id>\d+)/publish', [
            'methods' => 'POST',
            'callback' => [self::class, 'publish_post'],
            'permission_callback' => [self::class, 'can_publish'],
        ]);

        register_rest_route(self::NS, '/media/base64', [
            'methods' => 'POST',
            'callback' => [self::class, 'media_base64'],
            'permission_callback' => [self::class, 'can_upload'],
        ]);

        register_rest_route(self::NS, '/media/import', [
            'methods' => 'POST',
            'callback' => [self::class, 'media_import'],
            'permission_callback' => [self::class, 'can_upload'],
        ]);


        register_rest_route(self::NS, '/media/(?P<id>\d+)', [
            'methods' => ['PATCH', 'POST'],
            'callback' => [self::class, 'update_media_metadata'],
            'permission_callback' => [self::class, 'can_upload'],
        ]);

        register_rest_route(self::NS, '/image/requirements', [
            'methods' => 'POST',
            'callback' => [self::class, 'image_requirements'],
            'permission_callback' => [self::class, 'can_read'],
        ]);

        register_rest_route(self::NS, '/posts/(?P<id>\d+)/featured', [
            'methods' => 'POST',
            'callback' => [self::class, 'set_featured'],
            'permission_callback' => [self::class, 'can_edit'],
        ]);

        register_rest_route(self::NS, '/products', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'list_products'],
                'permission_callback' => [self::class, 'can_products'],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'create_product'],
                'permission_callback' => [self::class, 'can_products'],
            ],
        ]);

        register_rest_route(self::NS, '/products/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'get_product'],
                'permission_callback' => [self::class, 'can_products'],
            ],
            [
                'methods' => ['PATCH', 'POST'],
                'callback' => [self::class, 'update_product'],
                'permission_callback' => [self::class, 'can_products'],
            ],
        ]);

        register_rest_route(self::NS, '/products/(?P<id>\d+)/publish', [
            'methods' => 'POST',
            'callback' => [self::class, 'publish_product'],
            'permission_callback' => [self::class, 'can_publish_products'],
        ]);
    }

    public static function status() {
        $woo = class_exists('WooCommerce') && function_exists('wc_get_product');
        return rest_ensure_response([
            'ok' => true,
            'plugin' => 'Advertpreneur Connect',
            'version' => ADPC_VERSION,
            'site' => home_url('/'),
            'user' => wp_get_current_user()->user_login,
            'capabilities' => [
                'edit_posts' => current_user_can('edit_posts'),
                'publish_posts' => current_user_can('publish_posts'),
                'upload_files' => current_user_can('upload_files'),
                'woocommerce' => $woo,
                'edit_products' => $woo && self::can_products(),
                'publish_products' => $woo && self::can_publish_products(),
            ],
            'content_types' => array_values(array_filter(['post', 'page', $woo ? 'product' : null])),
            'image_inputs' => ['base64', 'https_url', 'native_wp_rest_multipart'],
            'image_planning' => [
                'slot_aware' => true,
                'quality_validation' => true,
                'fixed_default_dimensions' => false,
            ],
            'seo' => ADPC_SEO::detect(),
            'taxonomy_management' => [
                'categories' => taxonomy_exists('category'),
                'tags' => taxonomy_exists('post_tag'),
                'product_categories' => taxonomy_exists('product_cat'),
                'product_tags' => taxonomy_exists('product_tag'),
            ],
        ]);
    }

    private static function allowed_content_types($requested = '') {
        $types = ['post', 'page'];
        if (post_type_exists('product')) $types[] = 'product';
        $requested = sanitize_key((string) $requested);
        if ($requested && in_array($requested, $types, true)) return [$requested];
        return $types;
    }

    public static function search_content(WP_REST_Request $request) {
        $q = trim((string) $request->get_param('q'));
        $types = self::allowed_content_types($request->get_param('type'));
        $query = new WP_Query([
            's' => $q,
            'post_type' => $types,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => min(50, max(1, (int) ($request->get_param('limit') ?: 20))),
            'orderby' => 'relevance',
        ]);
        $items = [];
        foreach ($query->posts as $post) {
            // A user with edit_posts cannot necessarily read another author's
            // unpublished drafts. Never reveal their titles or excerpts in search.
            if ($post->post_status !== 'publish' && !current_user_can('edit_post', $post->ID)) continue;
            $text = ADPC_Quality::normalized_text(ADPC_Quality::content_for_post($post));
            $items[] = [
                'id' => $post->ID,
                'type' => $post->post_type,
                'status' => $post->post_status,
                'title' => get_the_title($post),
                'slug' => $post->post_name,
                'url' => get_permalink($post),
                'excerpt' => wp_trim_words($text, 45),
                'modified_gmt' => get_post_modified_time('c', true, $post),
            ];
        }
        return rest_ensure_response(['query' => $q, 'types' => $types, 'count' => count($items), 'items' => $items]);
    }

    public static function get_content(WP_REST_Request $request) {
        $id = (int) $request['id'];
        $post = get_post($id);
        if (!$post || !in_array($post->post_type, self::allowed_content_types(), true)) {
            return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        }
        if (!current_user_can('edit_post', $id) && $post->post_status !== 'publish') {
            return new WP_Error('adpc_forbidden', 'Cannot read this content.', ['status' => 403]);
        }

        $data = [
            'id' => $id,
            'type' => $post->post_type,
            'status' => $post->post_status,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'url' => get_permalink($id),
            'edit_url' => get_edit_post_link($id, 'raw'),
            'featured_media' => (int) get_post_thumbnail_id($id),
            'modified_gmt' => get_post_modified_time('c', true, $post),
            'quality_attestation' => get_post_meta($id, self::META_QUALITY, true),
            'seo' => ADPC_SEO::read($id),
        ];
        if ($post->post_type === 'post') {
            $data['category_ids'] = wp_get_post_categories($id, ['fields' => 'ids']);
            $data['tag_ids'] = wp_get_post_tags($id, ['fields' => 'ids']);
        }
        if ($post->post_type === 'page') {
            $data['parent_id'] = (int) $post->post_parent;
            $data['menu_order'] = (int) $post->menu_order;
            $data['template'] = get_page_template_slug($id);
        }
        return rest_ensure_response($data);
    }

    public static function seo_status() {
        return rest_ensure_response(ADPC_SEO::detect());
    }

    public static function get_seo(WP_REST_Request $request) {
        $id = (int) $request['id'];
        $post = get_post($id);
        if (!$post) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $id) && $post->post_status !== 'publish') {
            return new WP_Error('adpc_forbidden', 'Cannot read SEO data for this content.', ['status' => 403]);
        }
        $seo = ADPC_SEO::read($id);
        if (is_wp_error($seo)) return $seo;
        return rest_ensure_response($seo);
    }

    public static function update_seo(WP_REST_Request $request) {
        $id = (int) $request['id'];
        $fields = $request->get_param('fields');
        if (!is_array($fields)) {
            // Also accept top-level generic fields for simple REST callers.
            $fields = [];
            foreach (['meta_title', 'meta_description', 'focus_keyword', 'canonical_url'] as $key) {
                if ($request->has_param($key)) $fields[$key] = $request->get_param($key);
            }
        }
        if (!get_post($id)) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $id)) return new WP_Error('adpc_forbidden', 'Cannot edit SEO for this post.', ['status' => 403]);
        $seo = ADPC_SEO::write($id, $fields);
        if (is_wp_error($seo)) return $seo;
        return rest_ensure_response(['ok' => true, 'post_id' => $id, 'seo' => $seo]);
    }


    private static function seo_lower($value) {
        $value = html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private static function seo_contains_exact($haystack, $needle) {
        $haystack = self::seo_lower($haystack);
        $needle = self::seo_lower($needle);
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }

    public static function seo_audit(WP_REST_Request $request) {
        $id = (int) $request['id'];
        $post = get_post($id);
        if (!$post) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $id) && $post->post_status !== 'publish') return new WP_Error('adpc_forbidden', 'Cannot audit this content.', ['status' => 403]);
        $seo = ADPC_SEO::read($id);
        if (is_wp_error($seo)) return $seo;
        $fields = isset($seo['fields']) && is_array($seo['fields']) ? $seo['fields'] : [];
        $keyword = trim((string) ($fields['focus_keyword'] ?? ''));
        $content_html = (string) $post->post_content;
        $content_text = self::seo_lower($content_html);
        $words = preg_split('/\s+/u', trim($content_text), -1, PREG_SPLIT_NO_EMPTY);
        $word_count = count($words);
        $first_words = array_slice($words, 0, max(1, (int) ceil($word_count * 0.10)));
        $first_ten = implode(' ', $first_words);
        $keyword_l = self::seo_lower($keyword);
        $keyword_count = $keyword_l !== '' ? substr_count($content_text, $keyword_l) : 0;
        $keyword_words = max(1, count(preg_split('/\s+/u', $keyword_l, -1, PREG_SPLIT_NO_EMPTY)));
        $density = $word_count > 0 ? round(($keyword_count * $keyword_words / $word_count) * 100, 3) : 0;

        $headings = [];
        if (preg_match_all('/<h([2-6])[^>]*>(.*?)<\/h\1>/isu', $content_html, $hm, PREG_SET_ORDER)) {
            foreach ($hm as $h) $headings[] = wp_strip_all_tags($h[2]);
        }
        $heading_hit = false;
        foreach ($headings as $heading) {
            if (self::seo_contains_exact($heading, $keyword)) { $heading_hit = true; break; }
        }

        $featured_id = (int) get_post_thumbnail_id($id);
        $featured_alt = $featured_id ? (string) get_post_meta($featured_id, '_wp_attachment_image_alt', true) : '';
        $has_image = $featured_id > 0 || preg_match('/<img\b/i', $content_html);
        $image_keyword_hit = $featured_id > 0 && self::seo_contains_exact($featured_alt, $keyword);

        $internal = 0; $external = 0;
        if (preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $content_html, $lm)) {
            $site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
            foreach ($lm[1] as $href) {
                $host = wp_parse_url(html_entity_decode($href), PHP_URL_HOST);
                if (!$host || $host === $site_host) $internal++; else $external++;
            }
        }

        $permalink = get_permalink($id);
        $slug_keyword = $keyword !== '' && strpos((string) $post->post_name, sanitize_title($keyword)) !== false;
        $toc_present = strpos($content_html, '<!-- wp:rank-math/toc-block') !== false
            || strpos($content_html, 'wp-block-rank-math-toc-block') !== false
            || strpos($content_html, 'id="rank-math-toc"') !== false
            || strpos($content_html, "id='rank-math-toc'") !== false;

        $checks = [
            'focus_keyword_set' => $keyword !== '',
            'keyword_in_seo_title' => self::seo_contains_exact((string) ($fields['meta_title'] ?? ''), $keyword),
            'keyword_in_meta_description' => self::seo_contains_exact((string) ($fields['meta_description'] ?? ''), $keyword),
            'keyword_in_url' => $slug_keyword,
            'keyword_in_first_10_percent' => self::seo_contains_exact($first_ten, $keyword),
            'keyword_in_content' => $keyword_count > 0,
            'keyword_in_subheading' => $heading_hit,
            'keyword_in_featured_alt' => $image_keyword_hit,
            'seo_title_has_number' => (bool) preg_match('/\d/', (string) ($fields['meta_title'] ?? '')),
            'has_table_of_contents' => $toc_present,
            'has_internal_link' => $internal > 0,
            'has_external_link' => $external > 0,
            'has_image' => (bool) $has_image,
        ];
        $errors = [];
        foreach ($checks as $name => $pass) if (!$pass) $errors[] = $name;
        return rest_ensure_response([
            'post_id' => $id,
            'focus_keyword' => $keyword,
            'checks' => $checks,
            'metrics' => [
                'word_count' => $word_count,
                'keyword_occurrences' => $keyword_count,
                'keyword_density_percent' => $density,
                'url' => $permalink,
                'url_characters' => function_exists('mb_strlen') ? mb_strlen((string) $permalink) : strlen((string) $permalink),
                'seo_title_characters' => function_exists('mb_strlen') ? mb_strlen((string) ($fields['meta_title'] ?? '')) : strlen((string) ($fields['meta_title'] ?? '')),
                'meta_description_characters' => function_exists('mb_strlen') ? mb_strlen((string) ($fields['meta_description'] ?? '')) : strlen((string) ($fields['meta_description'] ?? '')),
                'internal_links' => $internal,
                'external_links' => $external,
                'featured_media' => $featured_id,
                'featured_alt' => $featured_alt,
            ],
            'errors' => $errors,
            'pass' => empty($errors),
            'note' => 'Deterministic preflight modeled on common SEO checks. Plugin scores are advisory; do not keyword-stuff or reduce factual quality just to clear a score.',
        ]);
    }

    private static function allowed_taxonomy($taxonomy) {
        $allowed = ['category', 'post_tag', 'product_cat', 'product_tag'];
        return in_array($taxonomy, $allowed, true) && taxonomy_exists($taxonomy);
    }

    public static function list_terms(WP_REST_Request $request) {
        $taxonomy = sanitize_key((string) ($request->get_param('taxonomy') ?: 'category'));
        if (!self::allowed_taxonomy($taxonomy)) {
            return new WP_Error('adpc_taxonomy_unsupported', 'Unsupported or unavailable taxonomy.', ['status' => 400]);
        }
        $limit = min(200, max(1, (int) ($request->get_param('limit') ?: 100)));
        $search = sanitize_text_field((string) $request->get_param('search'));
        $args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => $limit,
            'orderby' => $search ? 'name' : 'count',
            'order' => $search ? 'ASC' : 'DESC',
        ];
        if ($search !== '') $args['search'] = $search;
        $terms = get_terms($args);
        if (is_wp_error($terms)) return $terms;
        $items = [];
        foreach ($terms as $term) {
            $items[] = [
                'id' => (int) $term->term_id,
                'taxonomy' => $taxonomy,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => (int) $term->count,
                'parent_id' => isset($term->parent) ? (int) $term->parent : 0,
                'description' => (string) $term->description,
            ];
        }
        return rest_ensure_response(['taxonomy' => $taxonomy, 'search' => $search, 'count' => count($items), 'items' => $items]);
    }

    private static function internal_urls_from_content($content) {
        $urls = [];
        if (preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', (string) $content, $matches)) {
            foreach ($matches[1] as $url) {
                $url = esc_url_raw(html_entity_decode($url));
                if ($url) $urls[] = untrailingslashit($url);
            }
        }
        return array_values(array_unique($urls));
    }

    public static function suggest_internal_links(WP_REST_Request $request) {
        $post_id = absint($request->get_param('post_id'));
        $query_text = trim((string) $request->get_param('query'));
        $limit = min(30, max(1, (int) ($request->get_param('limit') ?: 10)));
        $current = $post_id ? get_post($post_id) : null;
        if ($post_id && !$current) return new WP_Error('adpc_not_found', 'Current content not found.', ['status' => 404]);
        if ($current && $current->post_status !== 'publish' && !current_user_can('edit_post', $post_id)) {
            return new WP_Error('adpc_forbidden', 'Cannot read this content.', ['status' => 403]);
        }
        if ($query_text === '' && $current) $query_text = $current->post_title;
        if ($query_text === '') return new WP_Error('adpc_query_required', 'A search topic or post_id is required.', ['status' => 400]);

        $types = ['post', 'page'];
        if (post_type_exists('product')) $types[] = 'product';
        $query = new WP_Query([
            's' => sanitize_text_field($query_text),
            'post_type' => $types,
            'post_status' => 'publish',
            'posts_per_page' => min(50, max($limit * 2, $limit)),
            'orderby' => 'relevance',
            'post__not_in' => $post_id ? [$post_id] : [],
            'no_found_rows' => true,
        ]);

        $already = $current ? self::internal_urls_from_content($current->post_content) : [];
        $current_categories = $current && $current->post_type === 'post'
            ? wp_get_post_categories($post_id, ['fields' => 'ids'])
            : [];
        $items = [];
        foreach ($query->posts as $candidate) {
            $url = get_permalink($candidate);
            if (!$url) continue;
            $candidate_categories = $candidate->post_type === 'post'
                ? wp_get_post_categories($candidate->ID, ['fields' => 'ids'])
                : [];
            $shared = array_values(array_intersect($current_categories, $candidate_categories));
            $text = ADPC_Quality::normalized_text(ADPC_Quality::content_for_post($candidate));
            $items[] = [
                'id' => (int) $candidate->ID,
                'type' => $candidate->post_type,
                'title' => get_the_title($candidate),
                'url' => $url,
                'excerpt' => wp_trim_words($text, 38),
                'already_linked' => in_array(untrailingslashit($url), $already, true),
                'shared_category_ids' => $shared,
                'modified_gmt' => get_post_modified_time('c', true, $candidate),
            ];
            if (count($items) >= $limit) break;
        }

        return rest_ensure_response([
            'query' => $query_text,
            'post_id' => $post_id ?: null,
            'count' => count($items),
            'items' => $items,
            'note' => 'These are factual internal-link candidates from published site content. The client should choose contextually useful links and natural anchor text; do not force links solely for SEO scoring.',
        ]);
    }

    public static function create_draft(WP_REST_Request $request) {
        $type = $request->get_param('type') === 'page' ? 'page' : 'post';
        $title = sanitize_text_field((string) $request->get_param('title'));
        if ($title === '') return new WP_Error('adpc_title_required', 'Title is required.', ['status' => 400]);

        $dupes = get_posts([
            'post_type' => ['post', 'page'],
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'title' => $title,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        if ($dupes) return new WP_Error('adpc_duplicate_title', 'A post or page with this exact title already exists. Review or update the existing content instead of creating a duplicate.', ['status' => 409, 'existing_post_id' => (int) $dupes[0]]);

        if ($request->get_param('slug')) {
            $slug = sanitize_title($request->get_param('slug'));
            $existing = get_page_by_path($slug, OBJECT, ['post', 'page']);
            if ($existing) return new WP_Error('adpc_duplicate_slug', 'This slug already exists. Review the existing content instead of creating a duplicate.', ['status' => 409, 'existing_post_id' => (int) $existing->ID]);
        }

        $postarr = [
            'post_type' => $type,
            'post_status' => 'draft',
            'post_title' => $title,
            'post_content' => wp_kses_post((string) $request->get_param('content')),
            'post_excerpt' => sanitize_textarea_field((string) $request->get_param('excerpt')),
        ];
        if ($request->get_param('slug')) $postarr['post_name'] = sanitize_title($request->get_param('slug'));
        if ($type === 'page') {
            if ($request->has_param('parent_id')) $postarr['post_parent'] = absint($request->get_param('parent_id'));
            if ($request->has_param('menu_order')) $postarr['menu_order'] = (int) $request->get_param('menu_order');
        }

        $id = wp_insert_post($postarr, true);
        if (is_wp_error($id)) return $id;

        if ($type === 'post') self::apply_post_terms($id, $request);
        if ($type === 'page' && $request->has_param('template')) update_post_meta($id, '_wp_page_template', sanitize_text_field((string) $request->get_param('template')));

        self::clear_attestation($id);
        return rest_ensure_response(['ok' => true, 'post_id' => $id, 'type' => $type, 'status' => 'draft', 'edit_url' => get_edit_post_link($id, 'raw')]);
    }

    private static function apply_post_terms($id, WP_REST_Request $request) {
        if ($request->has_param('category_ids')) {
            $cats = self::int_array($request->get_param('category_ids'));
            wp_set_post_categories($id, $cats, false);
        }
        if ($request->has_param('tag_ids')) {
            $tags = self::int_array($request->get_param('tag_ids'));
            wp_set_object_terms($id, $tags, 'post_tag', false);
        }
    }

    public static function update_post(WP_REST_Request $request) {
        $id = (int) $request['id'];
        $post = get_post($id);
        if (!$post || !in_array($post->post_type, ['post', 'page'], true)) return new WP_Error('adpc_not_found', 'Post or page not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $id)) return new WP_Error('adpc_forbidden', 'Cannot edit this content.', ['status' => 403]);

        $json = $request->get_json_params();
        if (!is_array($json)) $json = [];
        $has = static function($key) use ($json, $request) {
            return array_key_exists($key, $json) || $request->has_param($key);
        };
        $get = static function($key) use ($json, $request) {
            return array_key_exists($key, $json) ? $json[$key] : $request->get_param($key);
        };

        $update = ['ID' => $id];
        $expected = [];
        if ($has('title')) {
            $expected['post_title'] = sanitize_text_field((string) $get('title'));
            $update['post_title'] = $expected['post_title'];
        }
        if ($has('content')) {
            $expected['post_content'] = wp_kses_post((string) $get('content'));
            $update['post_content'] = $expected['post_content'];
        }
        if ($has('excerpt')) {
            $expected['post_excerpt'] = sanitize_textarea_field((string) $get('excerpt'));
            $update['post_excerpt'] = $expected['post_excerpt'];
        }
        if ($has('slug')) {
            $expected['post_name'] = sanitize_title((string) $get('slug'));
            $update['post_name'] = $expected['post_name'];
        }
        if ($post->post_type === 'page') {
            if ($has('parent_id')) $update['post_parent'] = absint($get('parent_id'));
            if ($has('menu_order')) $update['menu_order'] = (int) $get('menu_order');
        }

        if (count($update) > 1) {
            $result = wp_update_post(wp_slash($update), true);
            if (is_wp_error($result)) return $result;
        }

        if ($post->post_type === 'post') self::apply_post_terms($id, $request);
        if ($post->post_type === 'page' && $has('template')) update_post_meta($id, '_wp_page_template', sanitize_text_field((string) $get('template')));

        clean_post_cache($id);
        $saved = get_post($id);
        $mismatches = [];
        foreach ($expected as $field => $value) {
            $actual = isset($saved->$field) ? (string) $saved->$field : '';
            if ($actual !== (string) $value) {
                $mismatches[$field] = ['expected' => $value, 'actual' => $actual];
            }
        }
        if (!empty($mismatches)) {
            return new WP_Error('adpc_update_mismatch', 'WordPress did not persist one or more requested fields.', [
                'status' => 409,
                'mismatches' => $mismatches,
            ]);
        }

        // Only invalidate a quality attestation when an input used by the
        // quality system actually changed. Taxonomy, slug, excerpt, template,
        // menu order, and other metadata do not affect structural/overlap checks.
        $quality_inputs_changed =
            ((string) $saved->post_title !== (string) $post->post_title) ||
            ((string) $saved->post_content !== (string) $post->post_content);

        if ($quality_inputs_changed) {
            self::clear_attestation($id);
        }

        return rest_ensure_response([
            'ok' => true,
            'post_id' => $id,
            'type' => $post->post_type,
            'status' => get_post_status($id),
            'saved' => [
                'title' => $saved->post_title,
                'slug' => $saved->post_name,
                'content_sha256' => hash('sha256', trim(preg_replace('/\s+/', ' ', $saved->post_content))),
            ],
            'quality_attestation_cleared' => $quality_inputs_changed,
            'quality_attestation_retained' => !$quality_inputs_changed,
        ]);
    }

    private static function quality_payload($post) {
        $content = ADPC_Quality::content_for_post($post);
        $context = ADPC_Quality::context_for_post($post);
        $structural = ADPC_Quality::structural_report($content, $context);
        $overlap = ADPC_Quality::site_overlap_report($post->ID);
        return [
            'content_sha256' => $structural['content_sha256'],
            'structural' => $structural,
            'site_overlap' => $overlap,
            'pass' => !empty($structural['structural_pass']) && !empty($overlap['pass']),
        ];
    }

    public static function quality_check(WP_REST_Request $request) {
        $post_id = (int) $request->get_param('post_id');
        if ($post_id) {
            $post = get_post($post_id);
            if (!$post) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
            if (!current_user_can('edit_post', $post_id) && $post->post_status !== 'publish') return new WP_Error('adpc_forbidden', 'Cannot read this content.', ['status' => 403]);
            return rest_ensure_response(self::quality_payload($post));
        }
        $content = (string) $request->get_param('content');
        $context = sanitize_key((string) ($request->get_param('context') ?: 'post'));
        if (!in_array($context, ['post', 'page', 'product'], true)) $context = 'post';
        $structural = ADPC_Quality::structural_report($content, $context);
        return rest_ensure_response(['content_sha256' => $structural['content_sha256'], 'structural' => $structural, 'site_overlap' => null, 'pass' => !empty($structural['structural_pass'])]);
    }

    public static function quality_overlap(WP_REST_Request $request) {
        $post_id = (int) $request->get_param('post_id');
        $post = $post_id ? get_post($post_id) : null;
        if (!$post) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $post_id) && $post->post_status !== 'publish') return new WP_Error('adpc_forbidden', 'Cannot read this content.', ['status' => 403]);
        return rest_ensure_response(ADPC_Quality::site_overlap_report($post_id));
    }

    public static function quality_attest(WP_REST_Request $request) {
        $post_id = (int) $request->get_param('post_id');
        $post = get_post($post_id);
        if (!$post) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $post_id)) return new WP_Error('adpc_forbidden', 'Cannot edit this content.', ['status' => 403]);

        $report = $request->get_param('report');
        if (!is_array($report)) return new WP_Error('adpc_report_required', 'A structured quality report is required.', ['status' => 400]);

        $required_true = ['passed', 'site_overlap_checked', 'repetition_checked', 'misleading_claims_checked', 'source_reviewed'];
        foreach ($required_true as $field) {
            if (empty($report[$field])) return new WP_Error('adpc_quality_failed', "Quality attestation requires {$field}=true.", ['status' => 422]);
        }

        $quality = self::quality_payload($post);
        if (empty($quality['structural']['structural_pass'])) {
            return new WP_Error('adpc_structural_quality_failed', 'Quality attestation blocked because deterministic structural checks failed.', ['status' => 422, 'report' => $quality['structural']]);
        }
        if (empty($quality['site_overlap']['pass'])) {
            return new WP_Error('adpc_site_overlap_failed', 'Quality attestation blocked because this content substantially overlaps existing site content of the same intent/type.', ['status' => 422, 'report' => $quality['site_overlap']]);
        }

        $hash = ADPC_Quality::content_hash_for_post($post);
        if (!empty($report['content_sha256']) && !hash_equals($hash, (string) $report['content_sha256'])) {
            return new WP_Error('adpc_hash_mismatch', 'Content changed since quality review.', ['status' => 409]);
        }
        $report['content_sha256'] = $hash;
        $report['content_type'] = $post->post_type;
        $report['attested_at_gmt'] = gmdate('c');
        $report['attested_by'] = wp_get_current_user()->user_login;
        update_post_meta($post_id, self::META_QUALITY, $report);
        return rest_ensure_response(['ok' => true, 'post_id' => $post_id, 'attestation' => $report]);
    }

    private static function assert_current_attestation($post) {
        $att = get_post_meta($post->ID, self::META_QUALITY, true);
        if (!is_array($att) || empty($att['passed'])) {
            return new WP_Error('adpc_quality_required', 'Publishing blocked: no passing quality attestation exists.', ['status' => 422]);
        }
        $current_hash = ADPC_Quality::content_hash_for_post($post);
        if (empty($att['content_sha256']) || !hash_equals($current_hash, (string) $att['content_sha256'])) {
            return new WP_Error('adpc_quality_stale', 'Publishing blocked: content changed after quality attestation.', ['status' => 409]);
        }
        return true;
    }

    public static function publish_post(WP_REST_Request $request) {
        $id = (int) $request['id'];
        $post = get_post($id);
        if (!$post || !in_array($post->post_type, ['post', 'page'], true)) return new WP_Error('adpc_not_found', 'Post or page not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $id) || !current_user_can($post->post_type === 'page' ? 'publish_pages' : 'publish_posts')) return new WP_Error('adpc_forbidden', 'Cannot publish this content.', ['status' => 403]);

        // Make publish idempotent. A retry after a network/tool timeout should
        // report the authoritative WordPress state instead of failing needlessly.
        if ($post->post_status === 'publish') {
            return rest_ensure_response([
                'ok' => true,
                'post_id' => $id,
                'type' => $post->post_type,
                'status' => 'publish',
                'url' => get_permalink($id),
                'verified' => true,
                'already_published' => true,
            ]);
        }

        $ok = self::assert_current_attestation($post);
        if (is_wp_error($ok)) return $ok;

        $result = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
        if (is_wp_error($result)) return $result;

        clean_post_cache($id);
        $published = get_post($id);

        // Some plugins/hooks can modify status during wp_update_post(). Verify
        // the persisted state and make one safe retry before claiming success.
        if (!$published || $published->post_status !== 'publish') {
            $retry = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
            if (is_wp_error($retry)) return $retry;
            clean_post_cache($id);
            $published = get_post($id);
        }

        if (!$published || $published->post_status !== 'publish') {
            return new WP_Error(
                'adpc_publish_not_persisted',
                'WordPress did not persist publish status after two attempts.',
                [
                    'status' => 409,
                    'post_id' => $id,
                    'actual_status' => $published ? $published->post_status : null,
                ]
            );
        }

        return rest_ensure_response([
            'ok' => true,
            'post_id' => $id,
            'type' => $published->post_type,
            'status' => $published->post_status,
            'url' => get_permalink($id),
            'verified' => true,
            'already_published' => false,
        ]);
    }

    private static function allowed_image_mimes() {
        return ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    }

    private static function gcd($a, $b) {
        $a = abs((int) $a);
        $b = abs((int) $b);
        if (!$a || !$b) return 1;
        while ($b) { $t = $b; $b = $a % $b; $a = $t; }
        return max(1, $a);
    }

    private static function ratio_label($width, $height) {
        $width = (int) $width;
        $height = (int) $height;
        if ($width <= 0 || $height <= 0) return null;
        $g = self::gcd($width, $height);
        return ($width / $g) . ':' . ($height / $g);
    }

    private static function parse_ratio($value) {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            $ratio = (float) $value;
            return $ratio > 0 ? $ratio : null;
        }
        $value = trim((string) $value);
        if (preg_match('/^\s*([0-9]+(?:\.[0-9]+)?)\s*[:\/]\s*([0-9]+(?:\.[0-9]+)?)\s*$/', $value, $m)) {
            $b = (float) $m[2];
            return $b > 0 ? ((float) $m[1] / $b) : null;
        }
        return null;
    }

    private static function registered_image_size($name) {
        $name = sanitize_key((string) $name);
        if (!$name) return null;
        if (function_exists('wp_get_registered_image_subsizes')) {
            $sizes = wp_get_registered_image_subsizes();
            if (!empty($sizes[$name]) && is_array($sizes[$name])) {
                return [
                    'width' => isset($sizes[$name]['width']) ? (int) $sizes[$name]['width'] : 0,
                    'height' => isset($sizes[$name]['height']) ? (int) $sizes[$name]['height'] : 0,
                    'crop' => !empty($sizes[$name]['crop']),
                    'name' => $name,
                ];
            }
        }
        return null;
    }

    private static function block_image_slot($post_id, $media_id = 0, $image_index = null) {
        $post = $post_id ? get_post($post_id) : null;
        if (!$post || !function_exists('parse_blocks')) return null;
        $wanted_index = ($image_index === null || $image_index === '') ? null : max(0, (int) $image_index);
        $counter = 0;
        $walk = function ($blocks) use (&$walk, &$counter, $media_id, $wanted_index) {
            foreach ((array) $blocks as $block) {
                if (($block['blockName'] ?? '') === 'core/image') {
                    $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];
                    $id = isset($attrs['id']) ? (int) $attrs['id'] : 0;
                    $matches = $media_id ? ($id === (int) $media_id) : ($wanted_index !== null && $counter === $wanted_index);
                    if ($matches) {
                        $width = isset($attrs['width']) && is_numeric($attrs['width']) ? (int) $attrs['width'] : 0;
                        $height = isset($attrs['height']) && is_numeric($attrs['height']) ? (int) $attrs['height'] : 0;
                        $ratio = isset($attrs['aspectRatio']) ? self::parse_ratio($attrs['aspectRatio']) : null;
                        if (!$ratio && $width > 0 && $height > 0) $ratio = $width / $height;
                        return [
                            'width' => $width,
                            'height' => $height,
                            'ratio' => $ratio,
                            'crop' => !empty($attrs['scale']) && $attrs['scale'] === 'cover',
                            'source' => 'content_block',
                            'block_index' => $counter,
                        ];
                    }
                    $counter++;
                }
                if (!empty($block['innerBlocks'])) {
                    $found = $walk($block['innerBlocks']);
                    if ($found) return $found;
                }
            }
            return null;
        };
        return $walk(parse_blocks($post->post_content));
    }

    private static function existing_media_slot($media_id) {
        $media_id = (int) $media_id;
        if (!$media_id || !wp_attachment_is_image($media_id)) return null;
        $meta = wp_get_attachment_metadata($media_id);
        $width = is_array($meta) && !empty($meta['width']) ? (int) $meta['width'] : 0;
        $height = is_array($meta) && !empty($meta['height']) ? (int) $meta['height'] : 0;
        if (!$width || !$height) return null;
        return [
            'width' => $width,
            'height' => $height,
            'ratio' => $width / $height,
            'crop' => false,
            'source' => 'existing_media',
            'media_id' => $media_id,
        ];
    }

    private static function resolve_image_requirements_from_params($params) {
        $usage = sanitize_key((string) ($params['usage'] ?? 'featured'));
        $allowed_usage = ['featured', 'inline', 'product_main', 'product_gallery'];
        if (!in_array($usage, $allowed_usage, true)) $usage = 'featured';

        $post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;
        $media_id = isset($params['media_id']) ? absint($params['media_id']) : 0;
        $image_index = $params['image_index'] ?? null;
        $provided_w = isset($params['placeholder_width']) ? absint($params['placeholder_width']) : 0;
        $provided_h = isset($params['placeholder_height']) ? absint($params['placeholder_height']) : 0;
        $provided_ratio = self::parse_ratio($params['placeholder_aspect'] ?? null);

        $slot = null;
        if ($provided_w || $provided_h || $provided_ratio) {
            if (!$provided_ratio && $provided_w > 0 && $provided_h > 0) $provided_ratio = $provided_w / $provided_h;
            $slot = [
                'width' => $provided_w,
                'height' => $provided_h,
                'ratio' => $provided_ratio,
                'crop' => !empty($params['allow_crop']),
                'source' => 'provided_placeholder',
            ];
        }

        if (!$slot && $usage === 'inline' && $post_id) {
            $slot = self::block_image_slot($post_id, $media_id, $image_index);
        }

        if (!$slot && $usage === 'featured') {
            $registered = self::registered_image_size('post-thumbnail');
            if ($registered && ($registered['width'] || $registered['height'])) {
                $ratio = ($registered['width'] > 0 && $registered['height'] > 0) ? ($registered['width'] / $registered['height']) : null;
                $slot = [
                    'width' => $registered['width'],
                    'height' => $registered['height'],
                    'ratio' => $ratio,
                    'crop' => !empty($registered['crop']),
                    'source' => 'theme_post_thumbnail',
                    'registered_size' => 'post-thumbnail',
                ];
            }
        }

        if (!$slot && in_array($usage, ['product_main', 'product_gallery'], true) && self::woo_ready() && function_exists('wc_get_image_size')) {
            $size_name = $usage === 'product_main' ? 'woocommerce_single' : 'woocommerce_thumbnail';
            $size = wc_get_image_size($size_name);
            if (is_array($size)) {
                $width = !empty($size['width']) ? (int) $size['width'] : 0;
                $height = !empty($size['height']) ? (int) $size['height'] : 0;
                $ratio = ($width > 0 && $height > 0) ? ($width / $height) : null;
                $slot = [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $ratio,
                    'crop' => !empty($size['crop']),
                    'source' => 'woocommerce_image_size',
                    'registered_size' => $size_name,
                ];
            }
        }

        if (!$slot) {
            $candidate_media = $media_id;
            if (!$candidate_media && $post_id && $usage === 'featured') $candidate_media = (int) get_post_thumbnail_id($post_id);
            if ($candidate_media) $slot = self::existing_media_slot($candidate_media);
        }

        if (!$slot) {
            $slot = ['width' => 0, 'height' => 0, 'ratio' => null, 'crop' => false, 'source' => 'unknown'];
        }

        $width = isset($slot['width']) ? (int) $slot['width'] : 0;
        $height = isset($slot['height']) ? (int) $slot['height'] : 0;
        $ratio = isset($slot['ratio']) && $slot['ratio'] ? (float) $slot['ratio'] : (($width > 0 && $height > 0) ? $width / $height : null);
        $preferred_w = 0;
        $preferred_h = 0;
        if ($width > 0 && $height > 0) {
            $preferred_w = $width * 2;
            $preferred_h = $height * 2;
            $long = max($preferred_w, $preferred_h);
            if ($long > 4096) {
                $scale = 4096 / $long;
                $preferred_w = max($width, (int) round($preferred_w * $scale));
                $preferred_h = max($height, (int) round($preferred_h * $scale));
            }
        }

        return [
            'usage' => $usage,
            'source' => $slot['source'],
            'target' => [
                'width' => $width ?: null,
                'height' => $height ?: null,
                'aspect_ratio' => $ratio ? round($ratio, 6) : null,
                'aspect_label' => ($width > 0 && $height > 0) ? self::ratio_label($width, $height) : null,
                'crop' => !empty($slot['crop']),
            ],
            'recommended_generation' => [
                'width' => $preferred_w ?: null,
                'height' => $preferred_h ?: null,
                'aspect_ratio' => $ratio ? round($ratio, 6) : null,
                'aspect_label' => ($width > 0 && $height > 0) ? self::ratio_label($width, $height) : null,
                'pixel_density' => ($preferred_w && $width) ? round($preferred_w / $width, 2) : null,
                'instruction' => $ratio
                    ? 'Generate for this exact slot ratio at the highest native quality available. Do not stretch. Keep important subjects inside the safe crop area.'
                    : 'Slot ratio is unknown. Inspect the actual placeholder before generating; do not invent fixed dimensions.',
            ],
            'quality_rules' => [
                'minimum_resolution_scale' => 1.0,
                'preferred_resolution_scale' => 2.0,
                'maximum_aspect_error_percent' => 2.5,
                'reject_near_blank' => true,
                'upscaling_allowed' => false,
            ],
            'requires_client_probe' => $slot['source'] === 'unknown',
            'details' => $slot,
        ];
    }

    public static function image_requirements(WP_REST_Request $request) {
        $params = $request->get_params();
        return rest_ensure_response(self::resolve_image_requirements_from_params(is_array($params) ? $params : []));
    }

    private static function sampled_flatness($bytes) {
        if (!function_exists('imagecreatefromstring')) return ['checked' => false, 'near_blank' => null, 'unique_samples' => null, 'channel_range' => null];
        $img = @imagecreatefromstring($bytes);
        if (!$img) return ['checked' => false, 'near_blank' => null, 'unique_samples' => null, 'channel_range' => null];
        $w = imagesx($img); $h = imagesy($img);
        if (!$w || !$h) { imagedestroy($img); return ['checked' => false, 'near_blank' => null, 'unique_samples' => null, 'channel_range' => null]; }
        $steps = 12;
        $colors = [];
        $min = [255,255,255]; $max = [0,0,0];
        for ($yi = 0; $yi < $steps; $yi++) {
            $y = min($h - 1, (int) round((($yi + 0.5) / $steps) * $h - 0.5));
            for ($xi = 0; $xi < $steps; $xi++) {
                $x = min($w - 1, (int) round((($xi + 0.5) / $steps) * $w - 0.5));
                $rgba = imagecolorat($img, max(0,$x), max(0,$y));
                $r = ($rgba >> 16) & 0xFF; $g = ($rgba >> 8) & 0xFF; $b = $rgba & 0xFF;
                $min[0]=min($min[0],$r); $min[1]=min($min[1],$g); $min[2]=min($min[2],$b);
                $max[0]=max($max[0],$r); $max[1]=max($max[1],$g); $max[2]=max($max[2],$b);
                $key = (($r >> 4) << 8) | (($g >> 4) << 4) | ($b >> 4);
                $colors[$key] = true;
            }
        }
        imagedestroy($img);
        $range = max($max[0]-$min[0], $max[1]-$min[1], $max[2]-$min[2]);
        $unique = count($colors);
        return [
            'checked' => true,
            'near_blank' => ($unique <= 2 && $range <= 8),
            'unique_samples' => $unique,
            'channel_range' => $range,
        ];
    }

    private static function validate_image_bytes($bytes, $mime, $image_info, WP_REST_Request $request) {
        $width = is_array($image_info) && !empty($image_info[0]) ? (int) $image_info[0] : 0;
        $height = is_array($image_info) && !empty($image_info[1]) ? (int) $image_info[1] : 0;
        if (!$width || !$height) return new WP_Error('adpc_unreadable_image', 'Image dimensions could not be decoded.', ['status' => 415]);

        $params = $request->get_params();
        $requirements = self::resolve_image_requirements_from_params(is_array($params) ? $params : []);
        $target = $requirements['target'];
        $target_w = !empty($target['width']) ? (int) $target['width'] : 0;
        $target_h = !empty($target['height']) ? (int) $target['height'] : 0;
        $target_ratio = !empty($target['aspect_ratio']) ? (float) $target['aspect_ratio'] : null;
        $source_ratio = $width / $height;
        $aspect_error = $target_ratio ? abs($source_ratio - $target_ratio) / $target_ratio * 100 : null;
        $resolution_scale = ($target_w > 0 && $target_h > 0) ? min($width / $target_w, $height / $target_h) : null;
        $allow_crop = $request->has_param('allow_crop') ? (bool) $request->get_param('allow_crop') : !empty($target['crop']);
        $allow_flat = (bool) $request->get_param('allow_flat_image');
        $strict_fit = $request->has_param('strict_fit') ? (bool) $request->get_param('strict_fit') : true;
        $warnings = [];

        if ($strict_fit && $resolution_scale !== null && $resolution_scale < 1.0) {
            return new WP_Error('adpc_image_too_small_for_slot', 'Image does not have enough native pixels for the target placeholder and would require upscaling.', [
                'status' => 422,
                'source' => ['width' => $width, 'height' => $height],
                'target' => $target,
                'resolution_scale' => round($resolution_scale, 3),
            ]);
        }

        if ($aspect_error !== null && $aspect_error > 2.5) {
            if ($allow_crop) $warnings[] = 'Aspect ratio differs from the slot; WordPress/theme cropping may remove edge content.';
            elseif ($strict_fit) {
                return new WP_Error('adpc_image_aspect_mismatch', 'Image aspect ratio does not match the target placeholder.', [
                    'status' => 422,
                    'source_aspect_ratio' => round($source_ratio, 6),
                    'target_aspect_ratio' => $target_ratio,
                    'aspect_error_percent' => round($aspect_error, 2),
                ]);
            } else $warnings[] = 'Aspect ratio does not match the target placeholder.';
        }

        $flatness = self::sampled_flatness($bytes);
        if (!empty($flatness['near_blank']) && !$allow_flat) {
            return new WP_Error('adpc_near_blank_image', 'Image appears blank or nearly uniform. Upload rejected before WordPress placement.', [
                'status' => 422,
                'flatness' => $flatness,
            ]);
        }
        if (!$flatness['checked']) $warnings[] = 'Blank-image sampling was unavailable on this host.';

        return [
            'pass' => true,
            'source' => [
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => round($source_ratio, 6),
                'mime' => $mime,
                'bytes' => strlen($bytes),
            ],
            'slot' => $requirements,
            'fit' => [
                'aspect_error_percent' => $aspect_error === null ? null : round($aspect_error, 2),
                'resolution_scale' => $resolution_scale === null ? null : round($resolution_scale, 3),
                'crop_allowed' => $allow_crop,
                'upscale_required' => $resolution_scale !== null ? ($resolution_scale < 1.0) : null,
            ],
            'flatness' => $flatness,
            'warnings' => $warnings,
        ];
    }

    public static function media_base64(WP_REST_Request $request) {
        $raw = (string) $request->get_param('data');
        if (strpos($raw, 'base64,') !== false) $raw = substr($raw, strpos($raw, 'base64,') + 7);
        $bytes = base64_decode(preg_replace('/\s+/', '', $raw), true);
        if ($bytes === false || $bytes === '') return new WP_Error('adpc_bad_base64', 'Invalid base64 image data.', ['status' => 400]);
        if (strlen($bytes) > 8 * 1024 * 1024) return new WP_Error('adpc_too_large', 'Image exceeds 8 MB limit.', ['status' => 413]);

        $mime = '';
        $image_info = null;
        if (function_exists('getimagesizefromstring')) {
            $image_info = @getimagesizefromstring($bytes);
            if (is_array($image_info) && !empty($image_info['mime'])) $mime = (string) $image_info['mime'];
        }
        if (!$mime && class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->buffer($bytes);
        }
        $allowed = self::allowed_image_mimes();
        if (!$mime || !isset($allowed[$mime])) return new WP_Error('adpc_bad_mime', 'Unsupported or unreadable image type.', ['status' => 415]);

        $quality = self::validate_image_bytes($bytes, $mime, $image_info, $request);
        if (is_wp_error($quality)) return $quality;

        $filename = sanitize_file_name((string) ($request->get_param('filename') ?: ('advertpreneur-' . time() . '.' . $allowed[$mime])));
        if (pathinfo($filename, PATHINFO_EXTENSION) === '') $filename .= '.' . $allowed[$mime];
        $upload = wp_upload_bits($filename, null, $bytes);
        if (!empty($upload['error'])) return new WP_Error('adpc_upload_failed', $upload['error'], ['status' => 500]);

        return self::insert_attachment($upload['file'], $upload['url'], $mime, $request, $quality);
    }

    public static function media_import(WP_REST_Request $request) {
        $url = esc_url_raw((string) $request->get_param('url'));
        if (!$url || stripos($url, 'https://') !== 0 || !wp_http_validate_url($url)) {
            return new WP_Error('adpc_bad_url', 'A valid public HTTPS image URL is required.', ['status' => 400]);
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url, 20);
        if (is_wp_error($tmp)) return $tmp;
        if (filesize($tmp) > 8 * 1024 * 1024) { @unlink($tmp); return new WP_Error('adpc_too_large', 'Image exceeds 8 MB limit.', ['status' => 413]); }

        $checked = wp_check_filetype_and_ext($tmp, basename(parse_url($url, PHP_URL_PATH)) ?: 'image');
        $mime = $checked['type'] ?: '';
        if (!array_key_exists($mime, self::allowed_image_mimes())) { @unlink($tmp); return new WP_Error('adpc_bad_mime', 'Unsupported image type.', ['status' => 415]); }

        $bytes = @file_get_contents($tmp);
        $image_info = $bytes !== false && function_exists('getimagesizefromstring') ? @getimagesizefromstring($bytes) : false;
        $quality = ($bytes !== false) ? self::validate_image_bytes($bytes, $mime, $image_info, $request) : new WP_Error('adpc_image_read_failed', 'Downloaded image could not be read.', ['status' => 415]);
        if (is_wp_error($quality)) { @unlink($tmp); return $quality; }

        $filename = sanitize_file_name((string) ($request->get_param('filename') ?: basename(parse_url($url, PHP_URL_PATH))));
        if (!$filename) $filename = 'advertpreneur-' . time() . '.' . self::allowed_image_mimes()[$mime];
        $file = ['name' => $filename, 'tmp_name' => $tmp];
        $post_id = (int) $request->get_param('post_id');
        $id = media_handle_sideload($file, $post_id, sanitize_text_field((string) $request->get_param('title')));
        if (is_wp_error($id)) { @unlink($tmp); return $id; }
        if ($request->get_param('alt_text')) update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($request->get_param('alt_text')));
        return rest_ensure_response(['ok' => true, 'media_id' => $id, 'url' => wp_get_attachment_url($id), 'mime' => get_post_mime_type($id), 'quality' => $quality]);
    }

    private static function insert_attachment($file, $url, $mime, WP_REST_Request $request, $quality = null) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $post_id = (int) $request->get_param('post_id');
        $attachment = [
            'post_mime_type' => $mime,
            'post_title' => sanitize_text_field((string) ($request->get_param('title') ?: pathinfo($file, PATHINFO_FILENAME))),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => $url,
        ];
        $id = wp_insert_attachment($attachment, $file, $post_id, true);
        if (is_wp_error($id)) return $id;
        $metadata = wp_generate_attachment_metadata($id, $file);
        wp_update_attachment_metadata($id, $metadata);
        if ($request->get_param('alt_text')) update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($request->get_param('alt_text')));
        if (is_array($quality) && !empty($quality['slot']['target']['width']) && !empty($quality['slot']['target']['height'])) {
            update_post_meta($id, '_adpc_image_slot', [
                'width' => (int) $quality['slot']['target']['width'],
                'height' => (int) $quality['slot']['target']['height'],
                'aspect' => isset($quality['slot']['target']['aspect_label']) ? sanitize_text_field((string) $quality['slot']['target']['aspect_label']) : '',
                'crop' => !empty($quality['slot']['target']['crop']),
            ]);
        }
        $stored_mime = get_post_mime_type($id) ?: $mime;
        $stored_file = get_attached_file($id);
        return rest_ensure_response([
            'ok' => true,
            'media_id' => $id,
            'url' => wp_get_attachment_url($id),
            'source_mime' => $mime,
            'mime' => $stored_mime,
            'stored_bytes' => ($stored_file && file_exists($stored_file)) ? filesize($stored_file) : null,
            'metadata' => $metadata,
            'quality' => $quality,
        ]);
    }

    public static function update_media_metadata(WP_REST_Request $request) {
        $id = (int) $request['id'];
        $attachment = get_post($id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new WP_Error('adpc_media_not_found', 'Media attachment not found.', ['status' => 404]);
        }
        if (!current_user_can('edit_post', $id)) {
            return new WP_Error('adpc_media_forbidden', 'Cannot edit this media attachment.', ['status' => 403]);
        }
        $changes = ['ID' => $id];
        if ($request->has_param('title')) $changes['post_title'] = sanitize_text_field((string) $request->get_param('title'));
        if ($request->has_param('caption')) $changes['post_excerpt'] = wp_kses_post((string) $request->get_param('caption'));
        if ($request->has_param('description')) $changes['post_content'] = wp_kses_post((string) $request->get_param('description'));
        if (count($changes) > 1) {
            $updated = wp_update_post($changes, true);
            if (is_wp_error($updated)) return $updated;
        }
        if ($request->has_param('alt_text')) {
            update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field((string) $request->get_param('alt_text')));
        }
        return rest_ensure_response([
            'ok' => true,
            'media_id' => $id,
            'url' => wp_get_attachment_url($id),
            'title' => get_the_title($id),
            'alt_text' => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
            'caption' => (string) get_post_field('post_excerpt', $id),
            'description' => (string) get_post_field('post_content', $id),
        ]);
    }

    public static function set_featured(WP_REST_Request $request) {
        $post_id = (int) $request['id'];
        $media_id = (int) $request->get_param('media_id');
        if (!get_post($post_id)) return new WP_Error('adpc_not_found', 'Content not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $post_id)) return new WP_Error('adpc_forbidden', 'Cannot edit this content.', ['status' => 403]);
        if (!wp_attachment_is_image($media_id)) return new WP_Error('adpc_not_image', 'media_id is not an image attachment.', ['status' => 400]);

        $file = get_attached_file($media_id);
        $bytes = ($file && file_exists($file)) ? @file_get_contents($file) : false;
        $image_info = ($bytes !== false && function_exists('getimagesizefromstring')) ? @getimagesizefromstring($bytes) : false;
        if ($bytes !== false && $image_info) {
            $probe = new WP_REST_Request('POST');
            $probe->set_param('post_id', $post_id);
            $probe->set_param('usage', 'featured');
            $probe->set_param('strict_fit', true);
            $slot = get_post_meta($media_id, '_adpc_image_slot', true);
            if (!is_array($slot)) $slot = [];
            $pw = $request->has_param('placeholder_width') ? (int) $request->get_param('placeholder_width') : (int) ($slot['width'] ?? 0);
            $ph = $request->has_param('placeholder_height') ? (int) $request->get_param('placeholder_height') : (int) ($slot['height'] ?? 0);
            $pa = $request->has_param('placeholder_aspect') ? sanitize_text_field((string) $request->get_param('placeholder_aspect')) : (string) ($slot['aspect'] ?? '');
            if ($pw > 0) $probe->set_param('placeholder_width', $pw);
            if ($ph > 0) $probe->set_param('placeholder_height', $ph);
            if ($pa !== '') $probe->set_param('placeholder_aspect', $pa);
            if ($request->has_param('allow_crop')) $probe->set_param('allow_crop', (bool) $request->get_param('allow_crop'));
            elseif (array_key_exists('crop', $slot)) $probe->set_param('allow_crop', (bool) $slot['crop']);
            if ($request->has_param('allow_flat_image')) $probe->set_param('allow_flat_image', (bool) $request->get_param('allow_flat_image'));
            $quality = self::validate_image_bytes($bytes, get_post_mime_type($media_id), $image_info, $probe);
            if (is_wp_error($quality)) return $quality;
        } else {
            $quality = ['pass' => null, 'warnings' => ['Attachment bytes could not be revalidated before placement.']];
        }

        $before = (int) get_post_thumbnail_id($post_id);
        $set = $before === $media_id ? true : set_post_thumbnail($post_id, $media_id);
        $after = (int) get_post_thumbnail_id($post_id);
        if (!$set && $after !== $media_id) {
            $full = wp_get_attachment_image_src($media_id, 'full');
            if (!$full || empty($full[0])) {
                return new WP_Error('adpc_featured_failed', 'Could not set featured image.', ['status' => 500]);
            }
            update_post_meta($post_id, '_thumbnail_id', $media_id);
            clean_post_cache($post_id);
            $after = (int) get_post_thumbnail_id($post_id);
            if ($after !== $media_id) {
                return new WP_Error('adpc_featured_failed', 'Could not set featured image.', ['status' => 500, 'before' => $before, 'after' => $after]);
            }
        }
        // Featured-image placement does not change the article body used by
        // ADPC_Quality::content_hash_for_post(). Clearing the attestation here
        // made an otherwise publish-ready draft become unattested immediately
        // before publish. The image itself has already passed validation above.
        $attestation = get_post_meta($post_id, self::META_QUALITY, true);
        $attestation_retained = is_array($attestation) && !empty($attestation['passed']);

        return rest_ensure_response([
            'ok' => true,
            'post_id' => $post_id,
            'media_id' => $media_id,
            'featured_media' => (int) get_post_thumbnail_id($post_id),
            'already_featured' => $before === $media_id,
            'url' => wp_get_attachment_url($media_id),
            'quality' => $quality,
            'quality_attestation_cleared' => false,
            'quality_attestation_retained' => $attestation_retained,
        ]);
    }

    private static function woo_ready() {
        return class_exists('WooCommerce') && function_exists('wc_get_product');
    }

    private static function require_woo() {
        if (!self::woo_ready()) return new WP_Error('adpc_woocommerce_unavailable', 'WooCommerce is not active on this site.', ['status' => 409]);
        return true;
    }

    private static function int_array($value) {
        if ($value === null || $value === '') return [];
        if (!is_array($value)) $value = preg_split('/\s*,\s*/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter(array_map('absint', $value))));
    }

    private static function product_response($product) {
        if (!$product) return null;
        $id = $product->get_id();
        return [
            'id' => $id,
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price' => $product->get_price(),
            'manage_stock' => $product->get_manage_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'featured' => $product->get_featured(),
            'virtual' => $product->get_virtual(),
            'category_ids' => $product->get_category_ids(),
            'tag_ids' => $product->get_tag_ids(),
            'image_id' => $product->get_image_id(),
            'gallery_image_ids' => $product->get_gallery_image_ids(),
            'url' => get_permalink($id),
            'edit_url' => get_edit_post_link($id, 'raw'),
            'modified_gmt' => get_post_modified_time('c', true, $id),
            'quality_attestation' => get_post_meta($id, self::META_QUALITY, true),
        ];
    }

    public static function list_products(WP_REST_Request $request) {
        $ok = self::require_woo();
        if (is_wp_error($ok)) return $ok;
        $args = [
            'limit' => min(50, max(1, (int) ($request->get_param('limit') ?: 20))),
            'page' => max(1, (int) ($request->get_param('page') ?: 1)),
            'orderby' => 'modified',
            'order' => 'DESC',
            'return' => 'objects',
        ];
        $status = sanitize_key((string) $request->get_param('status'));
        if ($status) $args['status'] = $status;
        $search = sanitize_text_field((string) $request->get_param('search'));
        if ($search !== '') $args['search'] = $search;
        $products = wc_get_products($args);
        $items = [];
        foreach ($products as $product) {
            if ($product->get_status() !== 'publish' && !current_user_can('edit_post', $product->get_id())) continue;
            $items[] = self::product_response($product);
        }
        return rest_ensure_response(['count' => count($items), 'items' => $items]);
    }

    public static function get_product(WP_REST_Request $request) {
        $ok = self::require_woo();
        if (is_wp_error($ok)) return $ok;
        $product = wc_get_product((int) $request['id']);
        if (!$product) return new WP_Error('adpc_product_not_found', 'Product not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $product->get_id()) && $product->get_status() !== 'publish') return new WP_Error('adpc_forbidden', 'Cannot read this product.', ['status' => 403]);
        return rest_ensure_response(self::product_response($product));
    }

    private static function apply_product_fields($product, WP_REST_Request $request, $creating = false) {
        if ($request->has_param('name')) $product->set_name(sanitize_text_field((string) $request->get_param('name')));
        if ($request->has_param('slug')) $product->set_slug(sanitize_title((string) $request->get_param('slug')));
        if ($request->has_param('description')) $product->set_description(wp_kses_post((string) $request->get_param('description')));
        if ($request->has_param('short_description')) $product->set_short_description(wp_kses_post((string) $request->get_param('short_description')));
        if ($request->has_param('sku')) $product->set_sku(wc_clean((string) $request->get_param('sku')));
        if ($request->has_param('regular_price')) $product->set_regular_price(wc_format_decimal((string) $request->get_param('regular_price')));
        if ($request->has_param('sale_price')) $product->set_sale_price(wc_format_decimal((string) $request->get_param('sale_price')));
        if ($request->has_param('manage_stock')) $product->set_manage_stock((bool) $request->get_param('manage_stock'));
        if ($request->has_param('stock_quantity')) $product->set_stock_quantity(wc_stock_amount($request->get_param('stock_quantity')));
        if ($request->has_param('stock_status')) {
            $stock = sanitize_key((string) $request->get_param('stock_status'));
            if (in_array($stock, ['instock', 'outofstock', 'onbackorder'], true)) $product->set_stock_status($stock);
        }
        if ($request->has_param('catalog_visibility')) {
            $visibility = sanitize_key((string) $request->get_param('catalog_visibility'));
            if (in_array($visibility, ['visible', 'catalog', 'search', 'hidden'], true)) $product->set_catalog_visibility($visibility);
        }
        if ($request->has_param('featured')) $product->set_featured((bool) $request->get_param('featured'));
        if ($request->has_param('virtual')) $product->set_virtual((bool) $request->get_param('virtual'));
        if ($request->has_param('category_ids')) $product->set_category_ids(self::int_array($request->get_param('category_ids')));
        if ($request->has_param('tag_ids')) $product->set_tag_ids(self::int_array($request->get_param('tag_ids')));
        if ($request->has_param('image_id')) {
            $image_id = absint($request->get_param('image_id'));
            if ($image_id && !wp_attachment_is_image($image_id)) throw new Exception('image_id is not an image attachment.');
            $product->set_image_id($image_id);
        }
        if ($request->has_param('gallery_image_ids')) {
            $ids = self::int_array($request->get_param('gallery_image_ids'));
            foreach ($ids as $image_id) if (!wp_attachment_is_image($image_id)) throw new Exception('gallery_image_ids must contain image attachments only.');
            $product->set_gallery_image_ids($ids);
        }
        if ($creating) $product->set_status('draft');
    }

    public static function create_product(WP_REST_Request $request) {
        $ok = self::require_woo();
        if (is_wp_error($ok)) return $ok;
        $name = sanitize_text_field((string) $request->get_param('name'));
        if ($name === '') return new WP_Error('adpc_name_required', 'Product name is required.', ['status' => 400]);

        $dupes = get_posts([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'title' => $name,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        if ($dupes) return new WP_Error('adpc_duplicate_product', 'A product with this exact name already exists.', ['status' => 409, 'existing_product_id' => (int) $dupes[0]]);

        $type = sanitize_key((string) ($request->get_param('type') ?: 'simple'));
        if (!in_array($type, ['simple', 'variable'], true)) return new WP_Error('adpc_product_type', 'Only simple and variable products are supported for creation in this version.', ['status' => 400]);

        try {
            $product = $type === 'variable' ? new WC_Product_Variable() : new WC_Product_Simple();
            self::apply_product_fields($product, $request, true);
            $id = $product->save();
            self::clear_attestation($id);
            return rest_ensure_response(['ok' => true, 'product' => self::product_response(wc_get_product($id))]);
        } catch (Throwable $e) {
            return new WP_Error('adpc_product_create_failed', $e->getMessage(), ['status' => 400]);
        }
    }

    public static function update_product(WP_REST_Request $request) {
        $ok = self::require_woo();
        if (is_wp_error($ok)) return $ok;
        $id = (int) $request['id'];
        $product = wc_get_product($id);
        if (!$product) return new WP_Error('adpc_product_not_found', 'Product not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $id)) return new WP_Error('adpc_forbidden', 'Cannot edit this product.', ['status' => 403]);

        $quality_fields = ['name', 'slug', 'description', 'short_description', 'category_ids', 'tag_ids', 'image_id', 'gallery_image_ids'];
        $clear_quality = false;
        foreach ($quality_fields as $field) if ($request->has_param($field)) { $clear_quality = true; break; }

        try {
            self::apply_product_fields($product, $request, false);
            $product->save();
            if ($clear_quality) self::clear_attestation($id);
            return rest_ensure_response(['ok' => true, 'product' => self::product_response(wc_get_product($id)), 'quality_attestation_cleared' => $clear_quality]);
        } catch (Throwable $e) {
            return new WP_Error('adpc_product_update_failed', $e->getMessage(), ['status' => 400]);
        }
    }

    public static function publish_product(WP_REST_Request $request) {
        $ok = self::require_woo();
        if (is_wp_error($ok)) return $ok;
        $id = (int) $request['id'];
        $post = get_post($id);
        $product = wc_get_product($id);
        if (!$post || !$product) return new WP_Error('adpc_product_not_found', 'Product not found.', ['status' => 404]);
        if (!current_user_can('edit_post', $id) || !self::can_publish_products()) return new WP_Error('adpc_forbidden', 'Cannot publish this product.', ['status' => 403]);
        $quality = self::assert_current_attestation($post);
        if (is_wp_error($quality)) return $quality;
        try {
            $product->set_status('publish');
            $product->save();
            return rest_ensure_response(['ok' => true, 'product' => self::product_response(wc_get_product($id))]);
        } catch (Throwable $e) {
            return new WP_Error('adpc_product_publish_failed', $e->getMessage(), ['status' => 400]);
        }
    }

    private static function clear_attestation($post_id) {
        delete_post_meta((int) $post_id, self::META_QUALITY);
    }
}
