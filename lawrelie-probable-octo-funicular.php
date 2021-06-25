<?php
namespace Lawrelie\ProbableOctoFunicular;
// Plugin Name: lawrelie-probable-octo-funicular
// Description: WordPress のユーザーとタームを関連付けるプラグイン
// Version: 0.1.0-alpha
// Requires at least: 5.5
// Tested up to: 5.7
// Requires PHP: 7.4
// Text Domain: lawrelie-probable-octo-funicular
use WP_Term;
$constantName = fn(string $name): string => __NAMESPACE__ . '\\' . $name;
$define = fn(string $name, ...$args): bool => \define($constantName($name), ...$args);
$define('META_KEYS', ['authorId']);
function filter_defaultMetaTypeMetadata($value, int $objectId, string $metaKey, bool $single, string $metaType) {
    if (0 === \strpos($metaKey, metaKey(''))) {
        return sanitizeMeta($value, $metaKey, $single, $metaType);
    }
    return $value;
}
function filter_editTerm(int $termId, int $ttId, string $taxonomy): void {
    if (!\is_admin() || !\current_user_can('manage_categories')) {
        return;
    }
    foreach (META_KEYS as $key) {
        $metaKey = metaKey($key);
        if (!isset($_POST['tag_ID'], $_POST[$metaKey]) || $termId !== (int) \filter_var($_POST['tag_ID'], \FILTER_SANITIZE_NUMBER_INT)) {
            continue;
        }
        \delete_term_meta($termId, $metaKey);
        \add_term_meta($termId, $metaKey, $_POST[$metaKey], true);
    }
}
function filter_init(): void {
    foreach (\get_taxonomies([], 'names') as $name) {
        \add_filter("{$name}_edit_form", __NAMESPACE__ . '\filter_taxonomyEditForm', 10, 2);
    }
}
function filter_taxonomyEditForm(WP_Term $tag, string $taxonomy): void {
    ?>
    <fieldset>
        <legend>lawrelie-probable-octo-funicular</legend>
        <p>ユーザーとタームの関連付け</p>
        <table class="form-table" role="presentation">
            <tbody>
                <tr class="form-field">
                    <?php
                    $metaKey = metaKey('authorId');
                    $id = \esc_attr("$metaKey--{$tag->term_id}");
                    ?>
                    <th scope="row"><label for="<?php echo $id; ?>">ユーザー</label></th>
                    <td>
                        <select id="<?php echo $id; ?>" name="<?php echo \esc_attr($metaKey); ?>">
                            <?php
                            $ancestor = $tag;
                            $authorId = 0;
                            $metaValue = sanitizeId(\get_term_meta($tag->term_id, $metaKey, true));
                            while (true) {
                                $ancestor = \get_term($ancestor->parent, $ancestor->taxonomy);
                                if (!($ancestor instanceof WP_Term)) {
                                    break;
                                }
                                $authorId = sanitizeId(\get_term_meta($ancestor->term_id, $metaKey, true));
                                if (!!$authorId) {
                                    break;
                                }
                            }
                            $authorId = !$authorId ? \get_metadata_default('term', $tag->term_id, $metaKey, true) : $authorId;
                            foreach ([0, ...\get_users()] as $user) {
                                $userId = !$user ? 0 : $user->ID;
                                ?>
                                <option value="<?php echo \esc_attr($userId); ?>" <?php \selected($userId, $metaValue); ?>><?php
                                    if (!$userId) {
                                        echo \esc_html('""（継承：');
                                        if (!$authorId) {
                                            echo \esc_html('""');
                                        } else {
                                            \the_author_meta('display_name', $authorId);
                                        }
                                        echo '）';
                                    } else {
                                        echo \esc_html($user->display_name);
                                    }
                                ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>
    <?php
}
function metaKey(string $key): string {
    return "lawrelieProbableOctoFunicular_$key";
}
function sanitizeId($var): int {
    $id = (int) \filter_var($var, \FILTER_SANITIZE_NUMBER_INT);
    return 1 > $id ? 0 : $id;
}
function sanitizeMeta($value, $metaKey, $single, $metaType) {
    $sanitized = [];
    foreach ($single || !\is_iterable($value) ? [$value] : $value as $v) {
        $sanitized[] = \sanitize_meta($metaKey, $v, $metaType);
    }
    return !$single ? $sanitized : $sanitized[0];
}
$filters = [
    'default_term_metadata' => ['filter_defaultMetaTypeMetadata' => [10, 5]],
    'edit_term' => ['filter_editTerm' => [10, 3]],
    'init' => ['filter_init' => []],
    'sanitize_term_meta_' . metaKey('authorId') => ['sanitizeId' => []],
];
foreach ($filters as $tag => $functionsToAdd) {
    foreach ($functionsToAdd as $functionToAdd => $args) {
        \add_filter($tag, $constantName($functionToAdd), ...$args);
    }
}
