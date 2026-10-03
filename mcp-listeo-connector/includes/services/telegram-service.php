<?php
/**
 * MCP Telegram Service
 * Sends notifications and reports to a Telegram Chat
 */
class MCP_Telegram_Service {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Test the connection to Telegram
     */
    public function test_connection( $token, $chat_id = '' ) {
        $url = "https://api.telegram.org/bot{$token}/getMe";
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $data['ok'] ) || ! $data['ok'] ) {
            return new WP_Error( 'telegram_error', $data['description'] ?? 'Invalid token.' );
        }

        $bot_info = $data['result'];

        // If chat_id is provided, try to send a test message
        if ( ! empty( $chat_id ) ) {
            $test_url = "https://api.telegram.org/bot{$token}/sendMessage";
            wp_remote_post( $test_url, array(
                'body' => array(
                    'chat_id' => $chat_id,
                    'text'    => "🔔 <b>MCP Connector:</b> Prueba de conexión exitosa desde el panel de administración.",
                    'parse_mode' => 'HTML'
                )
            ) );
        }

        return $bot_info;
    }

    /**
     * Send a standard message
     */
    public function send_message( $text ) {
        $token   = get_option( 'mcp_telegram_token', '' );
        $chat_id = get_option( 'mcp_telegram_chat_id', '' );

        if ( empty( $token ) || empty( $chat_id ) ) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $response = wp_remote_post( $url, array(
            'body' => array(
                'chat_id'    => $chat_id,
                'text'       => $text,
                'parse_mode' => 'HTML'
            ),
            'timeout' => 15
        ) );

        return ! is_wp_error( $response );
    }

    /**
     * Send a report for a batch process
     */
    public function send_batch_report( $city, $total, $success, $results_links ) {
        $header = "<b>🚀 MCP AGENT: Lote Finalizado en {$city}</b>\n\n";
        $stats  = "✅ Publicados: <b>{$success}</b>\n❌ Fallidos: <b>" . ($total - $success) . "</b>\n\n";
        
        $links = "<b>🔗 Nuevos Listados:</b>\n- " . implode( "\n- ", $results_links );
        
        return $this->send_message( $header . $stats . $links );
    }
}
