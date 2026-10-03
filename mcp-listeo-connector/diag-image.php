<?php
/**
 * Diagnostic tool for Image Sideloading
 */
require_once( 'wp-load.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );

$url_unsplash = 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf';
echo "Testing Unsplash download from: $url_unsplash\n";

// Add User-Agent filter used in plugin
$user_agent_filter = function( $args ) {
    $args['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    $args['timeout'] = 60;
    $args['sslverify'] = false;
    return $args;
};
add_filter( 'http_request_args', $user_agent_filter );

$id_un = media_sideload_image( $url_unsplash, 0, null, 'id' );

remove_filter( 'http_request_args', $user_agent_filter );

if ( is_wp_error( $id_un ) ) {
    echo "UNSPLASH FAILED: " . $id_un->get_error_message() . " (Code: " . $id_un->get_error_code() . ")\n";
} else {
    echo "UNSPLASH SUCCESS: ID $id_un\n";
}
