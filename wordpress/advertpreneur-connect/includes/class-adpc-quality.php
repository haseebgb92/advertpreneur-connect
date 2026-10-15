<?php
if (!defined('ABSPATH')) exit;

class ADPC_Quality {
    public static function normalized_text($html) {
        $text = wp_strip_all_tags((string) $html, true);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }

    public static function content_for_post($post) {
        if (!$post) return '';
        $html = (string) $post->post_content;
        if ($post->post_type === 'product' && trim((string) $post->post_excerpt) !== '') {
            $html .= "\n<p>" . (string) $post->post_excerpt . "</p>";
        }
        return $html;
    }

    public static function context_for_post($post) {
        if (!$post) return 'post';
        return in_array($post->post_type, ['post', 'page', 'product'], true) ? $post->post_type : 'post';
    }

    public static function content_hash($html) {
        return hash('sha256', self::normalized_text($html));
    }

    public static function content_hash_for_post($post) {
        return self::content_hash(self::content_for_post($post));
    }

    private static function shingles($text, $size = 5, $cap = 2500) {
        $text = mb_strtolower(self::normalized_text($text));
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $m);
        $words = $m[0] ?? [];
        $set = [];
        $n = count($words);
        if ($n < $size) return $set;
        $end = min($n - $size + 1, $cap);
        for ($i = 0; $i < $end; $i++) {
            $set[implode(' ', array_slice($words, $i, $size))] = true;
        }
        return $set;
    }

    private static function jaccard($a, $b) {
        if (!$a || !$b) return 0.0;
        $inter = 0;
        foreach ($a as $key => $_) if (isset($b[$key])) $inter++;
        $union = count($a) + count($b) - $inter;
        return $union > 0 ? $inter / $union : 0.0;
    }

    private static function title_similarity($a, $b) {
        $na = mb_strtolower(self::normalized_text($a));
        $nb = mb_strtolower(self::normalized_text($b));
        if ($na === '' || $nb === '') return 0.0;
        similar_text($na, $nb, $pct);
        return $pct / 100;
    }

    public static function site_overlap_report($post_id, $limit = 100) {
        $post = get_post((int) $post_id);
        if (!$post) return ['checked' => false, 'flags' => ['post_not_found'], 'matches' => []];

        $types = $post->post_type === 'product' ? ['product'] : ['post', 'page'];
        $target_shingles = self::shingles(self::content_for_post($post));
        $matches = [];
        $query = new WP_Query([
            'post_type' => $types,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'post__not_in' => [(int) $post_id],
            'posts_per_page' => min(250, max(1, (int) $limit)),
            'orderby' => 'modified',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        foreach ($query->posts as $other_id) {
            $other = get_post($other_id);
            if (!$other) continue;
            $body = self::jaccard($target_shingles, self::shingles(self::content_for_post($other)));
            $title = self::title_similarity($post->post_title, $other->post_title);
            if ($body >= 0.18 || $title >= 0.55) {
                $matches[] = [
                    'post_id' => (int) $other_id,
                    'type' => $other->post_type,
                    'title' => get_the_title($other_id),
                    'status' => get_post_status($other_id),
                    'url' => get_permalink($other_id),
                    'body_similarity' => round($body, 4),
                    'title_similarity' => round($title, 4),
                ];
            }
        }

        usort($matches, function ($a, $b) {
            $sa = max($a['body_similarity'], $a['title_similarity']);
            $sb = max($b['body_similarity'], $b['title_similarity']);
            return $sb <=> $sa;
        });

        $blocking = array_values(array_filter($matches, function ($m) {
            return $m['body_similarity'] >= 0.35 || $m['title_similarity'] >= 0.78;
        }));

        return [
            'checked' => true,
            'scope' => $types,
            'matches' => array_slice($matches, 0, 10),
            'blocking_matches' => array_slice($blocking, 0, 10),
            'flags' => $blocking ? ['high_site_overlap'] : [],
            'pass' => empty($blocking),
            'note' => 'Similarity is a conservative local-site signal, not a plagiarism verdict. Review close matches for search-intent cannibalization, repetitive merchandising copy, or unnecessary duplication.'
        ];
    }

    public static function structural_report($html, $context = 'post') {
        $text = self::normalized_text($html);
        $word_count = preg_match_all('/\b[\p{L}\p{N}’\'\-]+\b/u', $text, $m);

        $paragraphs = preg_split('/(?:\r?\n){2,}|<\/p>/i', (string) $html);
        $seen = [];
        $duplicates = [];
        foreach ($paragraphs as $p) {
            $p = trim(self::normalized_text($p));
            if (mb_strlen($p) < 80) continue;
            $key = hash('sha256', mb_strtolower($p));
            if (isset($seen[$key])) $duplicates[] = mb_substr($p, 0, 180);
            $seen[$key] = true;
        }

        preg_match_all('/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', (string) $html, $heads);
        $headings = array_values(array_filter(array_map([self::class, 'normalized_text'], $heads[2] ?? [])));
        $heading_seen = [];
        $duplicate_headings = [];
        foreach ($headings as $heading) {
            $k = mb_strtolower($heading);
            if (isset($heading_seen[$k])) $duplicate_headings[] = $heading;
            $heading_seen[$k] = true;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text);
        $sentence_seen = [];
        $duplicate_sentences = [];
        foreach ($sentences as $sentence) {
            $s = trim((string) $sentence);
            if (mb_strlen($s) < 60) continue;
            $k = hash('sha256', mb_strtolower($s));
            if (isset($sentence_seen[$k])) $duplicate_sentences[] = mb_substr($s, 0, 180);
            $sentence_seen[$k] = true;
        }

        $min_words = 250;
        if ($context === 'page') $min_words = 120;
        if ($context === 'product') $min_words = 80;

        $flags = [];
        if ($word_count < $min_words) $flags[] = 'very_thin_content';
        if (count($duplicates) > 0) $flags[] = 'duplicate_paragraphs';
        if (count($duplicate_sentences) > 1) $flags[] = 'repeated_sentences';
        if (count($duplicate_headings) > 0) $flags[] = 'duplicate_headings';

        $padding_phrases = [
            'in today\'s fast-paced world',
            'it is important to note that',
            'in conclusion, it is clear that',
            'whether you are a beginner or an expert',
            'delve into',
            'unlock the secrets',
            'game-changer',
            'ever-evolving landscape',
            'comprehensive guide will explore',
        ];
        $lower = mb_strtolower($text);
        $matched_padding = [];
        foreach ($padding_phrases as $phrase) {
            if (mb_strpos($lower, $phrase) !== false) $matched_padding[] = $phrase;
        }
        if ($matched_padding) $flags[] = 'generic_padding_language';

        return [
            'context' => $context,
            'minimum_word_signal' => $min_words,
            'content_sha256' => self::content_hash($html),
            'word_count' => (int) $word_count,
            'heading_count' => count($headings),
            'duplicate_paragraphs' => $duplicates,
            'duplicate_sentences' => $duplicate_sentences,
            'duplicate_headings' => $duplicate_headings,
            'generic_padding_matches' => $matched_padding,
            'flags' => array_values(array_unique($flags)),
            'structural_pass' => count($flags) === 0,
            'note' => 'Structural checks cannot establish factual truth. Source review and misleading-claim review are separately required before publication.'
        ];
    }
}
