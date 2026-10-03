<?php
/**
 * Error Log Viewer
 */
require_once( 'wp-load.php' );
$log_file = ini_get('error_log');
if ( $log_file && file_exists($log_file) ) {
    echo "<h1>Last 50 Error Log Entries:</h1>";
    $lines = file($log_file);
    $last_lines = array_slice($lines, -50);
    echo "<pre>" . implode("", $last_lines) . "</pre>";
} else {
    echo "No error log found at: " . $log_file;
    // Try a common path if not set
    $common_log = 'error_log';
    if ( file_exists($common_log) ) {
        echo "<h1>Found local error_log:</h1>";
        $lines = file($common_log);
        $last_lines = array_slice($lines, -50);
        echo "<pre>" . implode("", $last_lines) . "</pre>";
    }
}
