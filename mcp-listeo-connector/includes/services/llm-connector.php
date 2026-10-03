<?php
/**
 * MCP LLM Connector Service
 * Handles communication with AI providers (Gemini, OpenAI, etc.)
 */
class MCP_LLM_Connector {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Fetch available models for an AI provider
     */
    public function fetch_available_models( $provider, $api_key ) {
        if ( 'gemini' === $provider ) return $this->get_gemini_models( $api_key );
        if ( 'openai' === $provider ) return $this->get_openai_models( $api_key );
        if ( 'groq'   === $provider ) return $this->get_groq_models( $api_key );
        if ( 'openrouter' === $provider ) return $this->get_openrouter_models( $api_key );
        return array();
    }

    private function get_openrouter_models( $api_key ) {
        $url = "https://openrouter.ai/api/v1/models";
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $models = array();
        
        if ( isset( $data['data'] ) ) {
            foreach ( $data['data'] as $m ) {
                $models[] = $m['id'];
            }
        }
        return ! empty($models) ? array_slice($models, 0, 50) : array('google/gemini-flash-1.5', 'anthropic/claude-3-haiku');
    }

    private function get_groq_models( $api_key ) {
        $url = "https://api.groq.com/openai/v1/models";
        $response = wp_remote_get( $url, array(
            'headers' => array( 'Authorization' => 'Bearer ' . $api_key )
        ) );

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $models = array();
        
        if ( isset( $data['data'] ) ) {
            foreach ( $data['data'] as $m ) {
                // Filter to get the most relevant ones
                if ( strpos( $m['id'], 'llama' ) !== false || strpos( $m['id'], 'mixtral' ) !== false ) {
                    $models[] = $m['id'];
                }
            }
        }
        return ! empty($models) ? $models : array('llama-3.1-70b-versatile', 'llama-3.1-8b-instant');
    }

    private function get_gemini_models( $api_key ) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $api_key;
        $response = wp_remote_get( $url );
        
        if ( is_wp_error( $response ) ) return $response;
        
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $models = array();
        
        if ( isset( $data['models'] ) ) {
            foreach ( $data['models'] as $m ) {
                if ( isset( $m['name'] ) && strpos( $m['name'], 'models/' ) === 0 ) {
                    $name = str_replace( 'models/', '', $m['name'] );
                    // Filter just some useful ones for the user
                    if ( strpos( $name, 'gemini' ) !== false ) {
                        $models[] = $name;
                    }
                }
            }
        }
        return ! empty($models) ? $models : array('gemini-1.5-flash', 'gemini-1.5-pro');
    }

    private function get_openai_models( $api_key ) {
        $url = "https://api.openai.com/v1/models";
        $response = wp_remote_get( $url, array(
            'headers' => array( 'Authorization' => 'Bearer ' . $api_key )
        ) );

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $models = array();
        
        if ( isset( $data['data'] ) ) {
            foreach ( $data['data'] as $m ) {
                if ( strpos( $m['id'], 'gpt' ) === 0 ) {
                    $models[] = $m['id'];
                }
            }
        }
        return ! empty($models) ? $models : array('gpt-3.5-turbo', 'gpt-4o');
    }

    /**
     * Get batch suggestions based on a user query
     */
    public function get_batch_suggestions( $query ) {
        $provider = get_option( 'mcp_ai_provider', 'gemini' );
        $api_key  = get_option( 'mcp_ai_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Please configure your AI API Key in MCP settings.', 'mcp-listeo-connector' ) );
        }

        $system_prompt = "You are a Tourism Expert Agent. The user wants to create multiple listings. 
        Analyze the query and return a JSON array of objects. 
        Each object must have: 'title', 'category' (choose from: city, hotel, restaurant, site, gastrobar, glamping), and 'brief' (a 1 sentence selling point).
        Limit to maximum 15 suggestions. Return ONLY the JSON array.";

        return $this->call_ai( $system_prompt, $query, true );
    }

    /**
     * Generate full content for a specific listing
     */
    public function generate_full_content( $item_title, $category = 'site' ) {
        $system_prompt = $this->get_category_prompt( $category );
        $user_query = "Generate full professional content for: " . $item_title;

        return $this->call_ai( $system_prompt, $user_query, true );
    }

    /**
     * Get specific prompt based on category
     */
    private function get_category_prompt( $category ) {
        $base = "You are a Luxury Tourism Expert for the portal xueturismo.com. Generate a professional listing in JSON format.
        Fields required: 
        - 'content': A long, attractive HTML description (minimum 600 words, strictly in SPANISH) using H2, H3, bold, and lists. Focus on tourist value, nearby attractions, and practical travel info.
        - 'address': A real precise address for this place.
        - 'lat': Approximate latitude (number).
        - 'lng': Approximate longitude (number).
        - 'price': A realistic average price (number only).
        - 'keywords': 5-8 comma-separated SEO keywords.
        - 'image_search_query': A perfect Unsplash search query to find this place (in English).";

        switch ( $category ) {
            case 'hotel':
            case 'glamping':
                $base .= "\nFocus on comfort, luxury, unique views, amenities, and the 'experience of waking up there'.";
                break;
            case 'restaurant':
            case 'gastrobar':
                $base .= "\nFocus on the Chef's specialty, ambiance, mixology, and why it is a 'must-visit' for foodies.";
                break;
            case 'city':
                $base .= "\nFocus on local history, hidden gems, culture, and the 'soul of the municipality'.";
                break;
            default:
                $base .= "\nFocus on historical significance, best visiting hours, and adventure/discovery.";
                break;
        }

        $base .= "\nReturn ONLY the JSON object. Do not include markdown formatting like ```json.";
        return $base;
    }

    /**
     * Interactive Chat Handler
     */
    public function chat( $message, $history = array() ) {
        $provider = get_option( 'mcp_ai_provider', 'gemini' );
        $api_key  = get_option( 'mcp_ai_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Please configure your AI API Key.', 'mcp-listeo-connector' ) );
        }

        // Fetch site context for better suggestions
        $categories = get_terms( array( 'taxonomy' => 'listing_category', 'hide_empty' => false ) );
        $regions    = get_terms( array( 'taxonomy' => 'region', 'hide_empty' => false ) );
        
        $cat_list = array();
        foreach ( $categories as $c ) $cat_list[] = $c->name;
        
        $reg_list = array();
        foreach ( $regions as $r ) $reg_list[] = $r->name;

        $context = "\nCONTEXTO DEL SITIO:\n";
        $context .= "- Categorías disponibles: " . implode( ', ', $cat_list ) . "\n";
        $context .= "- Regiones/Ubicaciones: " . implode( ', ', $reg_list ) . "\n";

        $system_prompt = "Eres un Agente Experto en Turismo gestionando un directorio de WordPress con Listeo.
        TU OBJETIVO: Ayudar al usuario a encontrar o crear contenido de alta calidad.
        
        $context
        
        REGLA CRÍTICA 1: Si el usuario pide crear un SOLO listado, pregunta si prefiere 'Manual' (paso a paso) o 'Automático' (tú generas todo).
        REGLA CRÍTICA 2: Para sugerencias de LOTES o BÚSQUEDAS masivas, responde con un objeto JSON de tipo 'batch_checklist'.
        
        SÉ PRECISO: Usa las categorías y regiones reales del sitio proporcionadas arriba. No inventes categorías si ya existen similares.
        
        EJEMPLO DE RESPUESTA PARA LOTES (batch_checklist):
        ```json
        {
          \"type\": \"batch_checklist\",
          \"message\": \"He preparado un listado con los lugares más emblemáticos basados en tu búsqueda.\",
          \"tasks\": [
            {\"title\": \"Nombre del Sitio\", \"category\": \"slug_o_nombre_cat\", \"region\": \"slug_o_nombre_region\"}
          ]
        }
        ```
        Reglas importantes para las tareas:
        1. ESTRUCTURA: Separa SIEMPRE la categoría y la región. Si el usuario pide 'restaurantes en Villa de Leyva', cada tarea debe tener category: 'restaurante' y region: 'villa-de-leyva'.
        2. DATOS REALES: Usa los slugs de las categorías y regiones que te he proporcionado arriba.
        Después del JSON, añade: 'He preparado la lista en el panel lateral. Revísala y pulsa \"Publicar Seleccionados\" para empezar.'
        
        Sé conversacional, profesional y directo. Responde siempre en español.";

        $contents = array();
        
        foreach ( $history as $msg ) {
            $contents[] = array(
                'role'  => ( $msg['role'] === 'agent' || $msg['role'] === 'model' ) ? 'model' : 'user',
                'parts' => array( array( 'text' => $msg['text'] ) )
            );
        }

        $contents[] = array(
            'role'  => 'user',
            'parts' => array( array( 'text' => $message ) )
        );

        if ( 'gemini' === $provider ) {
            return $this->call_gemini_chat( $system_prompt, $contents );
        }

        if ( 'groq' === $provider ) {
            return $this->call_openai_chat_style( "https://api.groq.com/openai/v1/chat/completions", $system_prompt, $history, $message );
        }

        if ( 'openrouter' === $provider ) {
            return $this->call_openai_chat_style( "https://openrouter.ai/api/v1/chat/completions", $system_prompt, $history, $message );
        }

        if ( 'openai' === $provider ) {
            return $this->call_openai_chat_style( "https://api.openai.com/v1/chat/completions", $system_prompt, $history, $message );
        }

        return new WP_Error( 'not_implemented', 'Provider not implemented.' );
    }

    private function call_openai_chat_style( $url, $system, $history, $message ) {
        $api_key = get_option( 'mcp_ai_api_key' );
        $model   = get_option( 'mcp_ai_model' );
        
        $messages = array(
            array( 'role' => 'system', 'content' => $system )
        );

        foreach ( $history as $msg ) {
            $role = ( $msg['role'] === 'agent' || $msg['role'] === 'assistant' ) ? 'assistant' : 'user';
            $messages[] = array( 'role' => $role, 'content' => $msg['text'] );
        }

        $messages[] = array( 'role' => 'user', 'content' => $message );

        $body = array(
            'model'    => $model,
            'messages' => $messages,
            'temperature' => 0.7
        );

        $response = wp_remote_post( $url, array(
            'headers' => array( 
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer $api_key",
                'HTTP-Referer'  => get_home_url(),
                'X-Title'       => 'MCP Listeo Connector'
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 180
        ) );

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return $data['choices'][0]['message']['content'];
        }

        return new WP_Error( 'ai_error', 'Invalid response from Chat AI: ' . wp_remote_retrieve_body( $response ) );
    }

    private function call_gemini_chat( $system, $contents ) {
        $api_key = get_option( 'mcp_ai_api_key' );
        $model   = get_option( 'mcp_ai_model', 'gemini-1.5-flash' );
        if ( empty( $model ) ) $model = 'gemini-1.5-flash';

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;

        $body = array(
            'system_instruction' => array(
                'parts' => array( array( 'text' => $system ) )
            ),
            'contents' => $contents,
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            )
        );

        $response = wp_remote_post( $url, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 180
        ) );

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return new WP_Error( 'ai_error', 'Invalid response from Gemini Chat: ' . wp_remote_retrieve_body( $response ) );
    }

    /**
     * Generic AI Caller
     */
    private function call_ai( $system, $user, $is_json = false ) {
        $provider = get_option( 'mcp_ai_provider', 'gemini' );
        $api_key  = get_option( 'mcp_ai_api_key' );

        // Implementation for Gemini (default)
        if ( 'gemini' === $provider ) {
            return $this->call_gemini( $system, $user );
        }

        // Implementation for Groq
        if ( 'groq' === $provider ) {
            return $this->call_groq( $system, $user );
        }

        // Implementation for OpenRouter
        if ( 'openrouter' === $provider ) {
            return $this->call_openrouter( $system, $user );
        }

        // Implementation for OpenAI (generic)
        if ( 'openai' === $provider ) {
            return $this->call_openai_style( "https://api.openai.com/v1/chat/completions", $system, $user );
        }

        return new WP_Error( 'not_implemented', 'Provider not implemented.' );
    }

    private function call_groq( $system, $user ) {
        $api_key = get_option( 'mcp_ai_api_key' );
        $model   = get_option( 'mcp_ai_model', 'llama-3.1-70b-versatile' );
        if ( empty( $model ) ) $model = 'llama-3.1-70b-versatile';

        return $this->call_openai_style( "https://api.groq.com/openai/v1/chat/completions", $system, $user, $api_key, $model );
    }

    private function call_openrouter( $system, $user ) {
        $api_key = get_option( 'mcp_ai_api_key' );
        $model   = get_option( 'mcp_ai_model', 'google/gemini-flash-1.5' );
        if ( empty( $model ) ) $model = 'google/gemini-flash-1.5';

        return $this->call_openai_style( "https://openrouter.ai/api/v1/chat/completions", $system, $user, $api_key, $model );
    }

    private function call_openai_style( $url, $system, $user, $api_key = null, $model = null ) {
        if ( ! $api_key ) $api_key = get_option( 'mcp_ai_api_key' );
        if ( ! $model )   $model   = get_option( 'mcp_ai_model' );

        $body = array(
            'model' => $model,
            'messages' => array(
                array( 'role' => 'system', 'content' => $system ),
                array( 'role' => 'user', 'content' => $user )
            ),
            'temperature' => 0.2
        );

        $response = wp_remote_post( $url, array(
            'headers' => array( 
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer $api_key",
                'HTTP-Referer'  => get_home_url(), // Recommended for OpenRouter
                'X-Title'       => 'MCP Listeo Connector'
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 180
        ) );

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            $text = $data['choices'][0]['message']['content'];
            
            // Extraction logic for JSON if expected
            if ( preg_match( '/\{.*\}|\[.*\]/s', $text, $matches ) ) {
                $json_text = $matches[0];
                $json = json_decode( $json_text, true );
                if ( $json ) return $json;
            }

            return $text;
        }

        return new WP_Error( 'ai_error', 'Invalid response from AI provider (OpenAI Style): ' . wp_remote_retrieve_body( $response ) );
    }

    private function call_gemini( $system, $user ) {
        $api_key = get_option( 'mcp_ai_api_key' );
        $model   = get_option( 'mcp_ai_model', 'gemini-1.5-flash' );
        if ( empty( $model ) ) $model = 'gemini-1.5-flash';

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;

        $body = array(
            'contents' => array(
                array(
                    'role' => 'user',
                    'parts' => array(
                        array( 'text' => "SYSTEM INSTRUCTIONS: " . $system . "\n\nUSER REQUEST: " . $user )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.2, // Lower temperature for more stable JSON
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 4096,
            ),
            'safetySettings' => array(
                array( 'category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE' ),
                array( 'category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE' ),
                array( 'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE' ),
                array( 'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE' )
            )
        );

        $response = wp_remote_post( $url, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 180
        ) );

        if ( is_wp_error( $response ) ) return $response;

        $body_content = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_content, true );
        
        if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            // Clean markdown if any
            $text = preg_replace('/^```json\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
            
            $json = json_decode( $text, true );
            return $json ? $json : $text;
        }

        // Handle blocked reason
        if ( isset( $data['candidates'][0]['finishReason'] ) && $data['candidates'][0]['finishReason'] === 'SAFETY' ) {
            return new WP_Error( 'ai_blocked', 'Request blocked by Gemini safety filters.' );
        }

        $error_msg = 'Invalid response from Gemini.';
        if ( isset( $data['error']['message'] ) ) {
            $error_msg .= ' Message: ' . $data['error']['message'];
        } else {
            $error_msg .= ' Response: ' . substr($body_content, 0, 200);
        }

        return new WP_Error( 'ai_error', $error_msg );
    }
}
