<?php
use Lawrelie\WordPress\TermAuthor as lwpta;
require_once __DIR__ . '/lawrelie-term-author.php';
$terms = get_terms();
if (is_wp_error($terms)) {
    return;
}
foreach ($terms as $term) {
    foreach (lwpta\META_KEYS as $key) {
        delete_term_meta($term->term_id, lwpta\metaKey($key));
    }
}
