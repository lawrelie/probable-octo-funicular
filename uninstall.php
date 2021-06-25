<?php
use Lawrelie\ProbableOctoFunicular as lpof;
$terms = get_terms();
if (is_wp_error($terms)) {
    return;
}
foreach ($terms as $term) {
    foreach (lpof\META_KEYS as $key) {
        delete_term_meta($term->term_id, lpof\metaKey($key));
    }
}
