<?php
/**
 * Diagnostic File: Check metadata for a listing
 * Usage: https://xueturismo.com/diag-gallery.php?id=POST_ID
 */
define('WP_USE_THEMES', false);
require_once('wp-load.php');

$id = $_GET['id'] ?? 4567; // Roma by default
echo "<h1>Diagnostic Metadata for Post ID: $id</h1>";

$meta = get_post_meta($id);

echo "<pre>";
foreach ($meta as $key => $values) {
    echo "<strong>$key:</strong> ";
    foreach ($values as $value) {
        if (is_serialized($value)) {
            print_r(unserialize($value));
        } else {
            echo htmlspecialchars($value);
        }
        echo "\n";
    }
}
echo "</pre>";
