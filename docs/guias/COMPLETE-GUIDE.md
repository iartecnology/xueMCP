# Guía Completa: MCP Listeo Connector
## Cómo Crear, Usar e Implementar Habilidades en Agentes IA y n8n

---

## Tabla de Contenido

1. [Arquitectura del Sistema](#1-arquitectura-del-sistema)
2. [ Anatomía de una Ability](#2--anatomía-de-una-ability)
3. [Cómo Crear una Nueva Habilidad](#3-cmo-crear-una-nueva-habilidad)
4. [El Sistema de Registro](#4-el-sistema-de-registro)
5. [ La API REST del MCP](#5-la-api-rest-del-mcp)
6. [ Uso con Claude Desktop / Perplexity](#6-uso-con-claude-desktop--perplexity)
7. [Implementación en n8n](#7-implementación-en-n8n)
8. [Automatización Completa](#8-automatización-completa)
9. [Errores y Debugging](#9-errores-y-debugging)
10. [Seguridad](#10-seguridad)

---

## 1. Arquitectura del Sistema

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Agente IA      │────▶│  MCP Server      │────▶│   WordPress     │
│ (Claude/n8n)    │     │  (index.js)     │     │   + Listeo      │
└─────────────────┘     └──────────────────┘     └─────────────────┘
        │                        │                        │
        │  MCP Protocol        │  REST API           │  WP REST
        │  (JSON-RPC)        │  (HTTP)            │  + Abilities
        │                    │                   │
        │              ┌─────▼─────┐             │  ┌──────────────┐
        │              │  stdio   │             │  │ MCP Plugin  │
        └────────────▶│ transport│             └──▶│ (PHP)      │
                     └──────────┘                └────────────┘
```

### Componentes

| Componente | Archivo | Función |
|-----------|--------|---------|
| **MCP Server** | `index.js` | Servidor que se conecta via stdio, traduce llamadas REST |
| **Plugin WP** | `mcp-listeo-connector.php` | Carga el sistema de abilities |
| **Registry** | `mcp-registry.php` | Registro central de abilities |
| **REST API** | `mcp-rest-api.php` | Endpoints `/tools`, `/call`, `/chat` |
| **Abilities** | `abilities/*.php` | Funciones individuales (get-listings, create-listing, etc.) |
| **Helpers** | `helpers/*.php` | Utilidades (listeo-api, wp-posts-api, dokan-api) |

---

## 2. Anatomía de una Ability

Una ability es una función registrada que puede ser llamada via MCP. Tiene esta estructura:

```php
MCP_Registry::register_ability( array(
    'id'          => 'proveedor/nombre-de-ability',  // ID único con prefijo
    'title'       => 'Título Legible para Humanos',
    'description' => 'Qué hace esta ability (usado por el LLM)',
    'scope'       => 'publish_posts',             // Capability de WP
    'callback'   => function( $args ) {          // Función que ejecuta
        // Tu código aquí
        return array( 'resultado' => 'valor' );
    },
    'schema'      => array(                      // Parámetros esperados
        'properties' => array(
            'parametro1' => array( 'type' => 'string', 'required' => true ),
            'parametro2' => array( 'type' => 'number' ),
        )
    )
) );
```

### Estructura del Schema

```php
'schema' => array(
    'type'       => 'object',
    'properties' => array(
        'title' => array( 
            'type'        => 'string',
            'description' => 'Título del listing',
            'required'   => true
        ),
        'lat'  => array( 
            'type'        => 'number', 
            'description' => 'Latitud GPS'
        ),
        // Tipos: string, number, boolean, array, object
    ),
    'required' => array( 'title' )  // Campos obligatorios
)
```

---

## 3. Cómo Crear una Nueva Habilidad

### Paso 1: Crear el archivo

Crea un nuevo archivo en `mcp-listeo-connector/includes/abilities/`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: ejemplo/saludar
 * Saluda a un usuario por nombre
 */
MCP_Registry::register_ability( array(
    'id'          => 'ejemplo/saludar',
    'title'       => 'Saludar Usuario',
    'description' => 'Devuelve un saludo personalizado. Útil para probar la conexión.',
    'scope'       => 'read',  // capability de WP (read, edit_posts, publish_posts, etc.)
    'callback'    => function( $args ) {
        // Obtener parámetros
        $nombre = $args['nombre'] ?? 'Visitante';
        $idioma = $args['idioma'] ?? 'es';
        
        // Procesar
        $saludos = array(
            'es' => "¡Hola, $nombre! Bienvenido a xueturismo.com",
            'en' => "Hello, $nombre! Welcome to xueturismo.com",
            'fr' => "Bonjour, $nombre! Bienvenue sur xueturismo.com"
        );
        
        $mensaje = $saludos[$idioma] ?? $saludos['es'];
        
        // Retornar resultado
        return array(
            'mensaje' => $mensaje,
            'idioma' => $idioma,
            'timestamp' => current_time( 'mysql' )
        );
    },
    'schema'      => array(
        'properties' => array(
            'nombre' => array( 
                'type'        => 'string',
                'description' => 'Nombre de la persona a saludar'
            ),
            'idioma' => array(
                'type'        => 'string',
                'description' => 'Código de idioma (es/en/fr)',
                'default'     => 'es'
            )
        )
    )
) );
```

### Paso 2: Cargar la ability

Edita `mcp-listeo-connector.php` y agrega:

```php
private function load_abilities() {
    $abilities_path = plugin_dir_path( __FILE__ ) . 'includes/abilities/';
    
    // ... abilities existentes ...
    
    // Nueva ability
    include_once $abilities_path . 'ejemplo-saludar.php';
}
```

### Paso 3: Verificar

Consulta `/wp-json/mcp-listeo/v1/tools` para ver tu nueva ability listada.

---

## 4. El Sistema de Registro

### MCP_Registry::register_ability()

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | string | Identificador único (formato: `proveedor/nombre`) |
| `title` | string | Nombre legible |
| `description` | string | Descripción para el LLM |
| `scope` | string | Capability de WP (`read`, `edit_posts`, `publish_posts`) |
| `callback` | callable | Función que ejecuta la lógica |
| `schema` | array | Definición de parámetros |
| `example_prompt` | string | Ejemplo de uso para el LLM |

### MCP_Registry::call()

```php
// Cómo se llama una ability desde código
$result = MCP_Registry::call( 'listeo/create-listing', array(
    'title' => 'Mi Nuevo Lugar',
    'content' => 'Descripción...',
    'lat' => 4.7110,
    'lng' => -74.0721
) );

// Retorna: array( 'isError' => false, 'data' => ... )
```

---

## 5. La API REST del MCP

### Endpoints Disponibles

| Método | Endpoint | Descripción | Auth |
|--------|----------|------------|------|
| `GET` | `/tools` | Lista todas las abilities disponibles | Token |
| `POST` | `/call` | Ejecuta una ability específica | Token |
| `POST` | `/chat` | Chat con el agente IA integrado | Admin |
| `POST` | `/auto-create` | Creación automática con IA | Admin |
| `GET` | `/models` | Lista modelos IA disponibles | Admin |
| `GET` | `/test-telegram` | Prueba conexión Telegram | Admin |

### Estructura de /call

```http
POST /wp-json/mcp-listeo/v1/call
Content-Type: application/json
X-MCP-Token: tu_token_seguro

{
    "ability": "listeo/create-listing",
    "args": {
        "title": "Nombre del lugar",
        "content": "Descripción",
        "address": "Dirección",
        "lat": 4.7110,
        "lng": -74.0721,
        "price": "Gratis",
        "keywords": "palabra1, palabra2",
        "image_url": "https://...",
        "status": "publish"
    }
}
```

### Respuesta Exitosa

```json
{
    "isError": false,
    "data": {
        "id": 5501,
        "link": "https://xueturismo.com/-/nombre-del-lugar/",
        "message": "Listing created successfully WITH IMAGE.",
        "image_status": "success"
    }
}
```

### Respuesta con Error

```json
{
    "isError": true,
    "message": "Missing 'title' parameter.",
    "code": 400
}
```

---

## 6. Uso con Claude Desktop / Perplexity

### Configuración

Añade a `claude_desktop_config.json`:

```json
{
    "mcpServers": {
        "listeo": {
            "command": "node",
            "args": ["/path/to/ListeoMCP/index.js"],
            "env": {
                "LISTEO_URL": "https://xueturismo.com/wp-json/mcp-listeo/v1",
                "MCP_TOKEN": "tu_token_aqui"
            }
        }
    }
}
```

### Herramientas Disponibles

Una vez configurado, Claude tiene acceso a todas las abilities registradas:

- `listeo/get-listings` - Buscar listados
- `listeo/create-listing` - Crear nuevo listing
- `listeo/get-listing-details` - Ver detalles
- `wp/get-post` - Obtener post por ID
- `wp/create-post` - Crear post genérico
- `wp/update-post` - Actualizar post
- `dokan/create-product` - Crear producto

### Ejemplo de Uso

```
Usuario: Publica un nuevo restaurante en Cartagena

Claude (automáticamente):
-> Llama listeo/create-listing
-> Pasa los datos del restaurante
-> Confirma la publicación
```

---

## 7. Implementación en n8n

### 7.1 Nodo HTTP Básico

```
URL: https://xueturismo.com/wp-json/mcp-listeo/v1/call
Method: POST
Authentication: Predefined Header
Header Name: X-MCP-Token
Header Value: {{ $credentials.mcp_token }}
```

### 7.2 Body JSON

```json
{
    "ability": "listeo/create-listing",
    "args": {
        "title": "{{ $json.title }}",
        "content": "{{ $json.content }}",
        "address": "{{ $json.address }}",
        "lat": {{ $json.lat }},
        "lng": {{ $json.lng }},
        "price": "{{ $json.price }}",
        "keywords": "{{ $json.keywords }}",
        "image_url": "{{ $json.image_url }}",
        "status": "publish"
    }
}
```

### 7.3 Workflow Completo de Publicación

```
┌─────────────────┐
│  Manual Trigger │
└────────┬────────┘
         │
         v
┌─────────────────┐     ┌─────────────────┐
│  Get Next Item  │────▶│  Search Images  │
│  (from queue)  │     │  (Bing/DuckDG)  │
└─────────────────┘     └────────┬────────┘
                                 │
                                 v
                         ┌───────────────┐
                         │  Test Image  │
                         │    URL      │
                         └──────┬──────┘
                                │
                    ┌───────────┴───────────┐
                    │                       │
               [Status 200]           [Status != 200]
                    │                       │
                    v                       v
          ┌─────────────────┐    ┌─────────────────┐
          │  Create Listing │    │  Try Next Image │
          │  (n8n HTTP)      │    │  (Loop)         │
          └─────────────────┘    └─────────────────┘
                    │
                    v
          ┌─────────────────┐
          │  Notify (Telegram)│
          │  or Slack       │
          └─────────────────┘
```

### 7.4 Nodo de Test de Imagen

```
URL: "{{ $json.image_url }}"
Method: HEAD
Timeout: 10000
Return: All Entries
```

### 7.5 Expresión de Verificación

```javascript
// En n8n puedes usar:
{{ $json.image_url.includes('unsplash.com') ? 'TRY' : 'SEARCH' }}
```

### 7.6 Configuración de Credenciales

En n8n, configura las credenciales:

```
Credential Type: Custom Header
Header Name: X-MCP-Token
Header Value: [tu_token_del_panel_wp]
```

---

## 8. Automatización Completa

### 8.1Flujo Automatizado con n8n

```
1. Trigger: Webhook o Schedule
2. Get Next Item: Leer de Google Sheets o base de datos
3. Search Image: Buscar en Bing/DuckDuckGo
4. Test Image: Verificar que la URL responde 200
5. Create Listing: Publicar via MCP API
6. Update Status: Marcar como publicado en la source
7. Notify: Enviar notificación Telegram/Slack
```

### 8.2 Configuración de Errores

```php
// En el callback de tu ability
try {
    // Tu lógica
} catch ( Exception $e ) {
    return MCP_Utils::send_json_error( $e->getMessage(), 500 );
}
```

### 8.3 Logging

```php
// Ver logs en WP Admin > MCP Connector > Ver Logs
MCP_Utils::log( "Procesando: " . $args['title'] );
```

---

## 9. Errores y Debugging

### Códigos de Error Comunes

| Código | Causa | Solución |
|--------|-----|---------|
| `400` | Parámetro faltante | Revisar schema |
| `403` | Token inválido | Verificar MCP_TOKEN |
| `404` | Ability no existe | Verificar ID en /tools |
| `500` | Error interno | Revisar debug.log |
| `IMAGE FAILED` | URL no accesible | Usar otra fuente |

### Debugging

```bash
# Ver abilities disponibles
curl -s https://tu-sitio.com/wp-json/mcp-listeo/v1/tools

# Ver respuesta cruda
curl -v -X POST https://tu-sitio.com/wp-json/mcp-listeo/v1/call \
  -H "Content-Type: application/json" \
  -H "X-MCP-Token: tu_token" \
  -d '{"ability":"listeo/create-listing","args":{"title":"Test"}}'
```

---

## 10. Seguridad

### Configuración de Token

1. Ve a **WP Admin > MCP Connector > Configuración**
2. Genera o ingresa tu token de seguridad
3. Configura el header `X-MCP-Token` en todas las requests

### Capabilities de WordPress

| Capability | Descripción |
|-----------|------------|
| `read` | Ver contenido público |
| `edit_posts` | Crear/editar posts |
| `publish_posts` | Publicar posts |
| `manage_options` | Acceso total (admin) |

### Deshabilitar Seguridad (Solo Desarrollo)

```
WP Admin > MCP Connector > Seguridad
-> "Deshabilitar validación": ON
```

> ⚠️ **NUNCA** hacer esto en producción.

---

## Anexo: Habilidades Existentes

### WordPress Core

| Ability | Descripción |
|---------|------------|
| `wp/get-post` | Obtener un post por ID |
| `wp/create-post` | Crear un post |
| `wp/update-post` | Actualizar un post |
| `wp/search-advanced` | Búsqueda avanzada |

### Listeo

| Ability | Descripción |
|---------|------------|
| `listeo/get-listings` | Listar directorios |
| `listeo/create-listing` | Crear listing |
| `listeo/get-listing-details` | Ver detalles |
| `listeo/get-listing-meta` | Ver metadata cruda |
| `listeo/create-booking` | Crear reserva |
| `listeo/get-user-dashboard` | Dashboard del usuario |

### Dokan (Marketplace)

| Ability | Descripción |
|---------|------------|
| `dokan/get-vendor-store` | Ver tienda |
| `dokan/create-product` | Crear producto |
| `dokan/manage-products` | Gestionar productos |

---

## Referencias

- **index.js**: Servidor MCP principal
- **mcp-listeo-connector.php**: Plugin principal
- **mcp-registry.php**: Sistema de registro
- **mcp-rest-api.php**: API REST
- **abilities/*.php**: Habilidades individuales
- **helpers/*.php**: Utilidades API
- **plan-colombia.md**: Plan de publicación

---

*Documento generado para ListeoMCP - xueturismo.com*
*Última actualización: 2026-04-19*