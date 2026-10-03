<?php
if ( ! defined( 'ABSPATH' ) ) exit;

MCP_Registry::register_ability( array(
    'id'          => 'utils/setup-temp-dir',
    'title'       => 'Setup Temp Directory',
    'description' => 'Creates the mcp-temp directory in wp-content for local image processing.',
    'callback'    => function() {
        $temp_dir = WP_CONTENT_DIR . '/mcp-temp/';
        if ( ! file_exists( $temp_dir ) ) {
            mkdir( $temp_dir, 0755, true );
            file_put_contents( $temp_dir . 'index.php', '<?php // Silence is golden' );
            return "Directory created at $temp_dir";
        }
        return "Directory already exists at $temp_dir";
    }
) );
