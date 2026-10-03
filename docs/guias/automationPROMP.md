# Automation PROMP - Publicación Automatizada de Listados

> **Proyecto**: ListeoMCP (xueturismo.com)  
> **Objetivo**: Automatizar la publicación de monumentos y lugares turisticos via n8n + MCP Listeo Connector

---

## 1. Prompt de Sistema para el Agente IA

```
Eres el experto en publicación del directorio turistico xueturismo.com.
Tu misian es crear nuevos listados (monumentos, lugares turisticos) de Colombia automatically.

## REGLA CRITICAL: Imagenes
Antes de publicar CUALQUIER listing, DEBES verificar que la imagen sea descargable:
1. Prueba la URL con: curl -s -o /dev/null -w "%{http_code}" "URL_IMAGEN" -m 10
2. Si devuelve 200, OK - usa esa imagen
3. SI devuelve 404, 429, 403 u otro error, BUSCA OTRA IMAGEN

FUENTES DE IMAGENES CONFIRMADAS QUE FUNCIONAN:
- picsum.photos (imagenes aleatorias de prueba)
- fastly.picsum.photos/seed/[nombre]/800/600.jpg (imagenes especificas)
- Wikimedia Commons (algunas URLs directas)

FUENTES QUE NO FUNCIONAN (el servidor WordPress no puede descargar):
- Unsplash direct URLs (requieren API key)
- Alamy, Adobe Stock (bloqueanDownloads)
- Kids.kiddle.co (bloquean hotlinking)

## DATOS REQUERIDOS para cada listing:
{
  "title": "Nombre del lugar",
  "content": "Descripcion completa (2-3 parrafos)",
  "address": "Direccion completa, Ciudad, Departamento, Colombia",
  "lat": NUMERO,
  "lng": NUMERO,
  "price": "Gratis" o rango de precios,
  "keywords": "palabras clave separadas por coma",
  "image_url": "URL directa de imagen (verificada antes)',
  "status": "publish"
}

## ENDPOINT DE PUBLICACION:
POST https://xueturismo.com/wp-json/mcp-listeo/v1/call
Headers:
  Content-Type: application/json
  X-MCP-Token: [TOKEN_CONFIGURADO]

Body:
{
  "ability": "listeo/create-listing",
  "args": { ... datos del listing ... }
}

## RESPUESTA EXITOSA:
{ "isError": false, "data": { "id": 1234, "link": "https://xueturismo.com/..." } }

## RESPUESTA CON ERROR DE IMAGEN:
{ "isError": false, "data": { "message": "IMAGE(S) FAILED: Not Found" } }
Si ves esto, el listing se creo pero SIN IMAGEN - busca otra URL y actualiza manualmente

## FLUJO AUTOMATICO:
1. Obtener datos del listing (de tu base de conocimiento o del usuario)
2. Buscar imagen en internet (Google, DuckDuckGo, Bing)
3. TESTEAR la URL de imagen ANTES de publicar
4. Si falla buscar otra - repetir paso 3
5. Publicar con image_url verificada
6. Confirmar resultado
```

---

## 2. Configuración de n8n

### 2.1HTTP Request Node - Publicar Listing

```
URL: https://xueturismo.com/wp-json/mcp-listeo/v1/call
Method: POST
Authentication: Predefined Header
Header Name: X-MCP-Token
Header Value: {{ $credentials.mcp_token }}

Headers:
Content-Type: application/json

Body (JSON):
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

### 2.2 HTTP Request Node - Testear Imagen

```
URL: "{{ $json.image_url }}"
Method: GET (o HEAD)
Timeout: 10000

Expression para verificar:
{{ $response.statusCode === 200 ? "OK" : "FAILED" }}
```

### 2.3 IF Node - Verificar Imagen

```
IF {{ $response.statusCode }} === 200
  -> Continuar a Publicar
ELSE
  -> Buscar otra imagen (loop)
```

---

## 3. Flujo Completo n8n

### 3.1 Workflow Principal

```
[Trigger: Manual / Schedule / Webhook]
     |
     v
[Function: Get Next Listing from Queue]
     |
     v
[HTTP Request: Search Images (Bing/DuckDuckGo)]
     |
     v
[Function: Extract Image URLs]
     |
     v
[Loop: For Each Image]
     |
     v
[HTTP Request: Test Image URL]
     |
     v
[IF: Status = 200]
     |
     +-- YES --> [HTTP Request: Create Listing] --> [End]
     |
     +-- NO --> [Continue Loop]
```

### 3.2Sub-Nodes

#### Search Images (DuckDuckGo)
```
URL: https://duckduckgo.com/i.js?q={{ encodeURIComponent(query) }}&vqd={{ vqd }}&o=json&f=1
Method: GET
```

#### Test Image URL
```
URL: {{ image_url }}
Method: HEAD
Timeout: 10000
```

---

## 4. Ejemplo: Publicar Monumento

### Input:
```json
{
  "title": "Castillo San Felipe de Barajas - Cartagena",
  "content": "El Castillo San Felipe de Barajas es la fortaleza colonial más grande de Sudamérica...",
  "address": "Cerro de San Lázaro, Cartagena, Bolívar, Colombia",
  "lat": 10.4205,
  "lng": -75.5268,
  "price": "Gratis",
  "keywords": "cartagena, fortaleza, castillo, historia, colonial, patrimonio"
}
```

### Paso 1: Buscar Imagen
```bash
# Buscar en DuckDuckGo: "Castillo San Felipe Barajas Cartagena"
# URLs encontradas:
# - https://kids.kiddle.co/... (NO funciona)
# - https://upload.wikimedia.org/... (probamos)
```

### Paso 2: Testear URL
```bash
curl -s -o /dev/null -w "%{http_code}" "https://upload.wikimedia.org/wikipedia/commons/..." -m 10
# Resultado: 200 -> OK
```

###Paso 3: Publicar
```json
{
  "ability": "listeo/create-listing",
  "args": {
    "title": "Castillo San Felipe de Barajas - Cartagena",
    "content": "...",
    "address": "...",
    "lat": 10.4205,
    "lng": -75.5268,
    "price": "Gratis",
    "keywords": "...",
    "image_url": "https://upload.wikimedia.org/wikipedia/commons/...",
    "status": "publish"
  }
}
```

### Respuesta Exitosa:
```json
{
  "isError": false,
  "data": {
    "id": 5498,
    "link": "https://xueturismo.com/-/castillo-san-felipe-barajas-cartagena/",
    "message": "Listing created successfully WITH IMAGE."
  }
}
```

---

## 5. Lista de Monumentos Pendientes (del plan-colombia.md)

| # | Monumento | Ubicación | Estado |
|---|---------|----------|-------|
| 1 | Castillo San Felipe de Barajas | Cartagena | ✅ |
| 2 | Castillo de San Fernando de Bocachica | Cartagena | ✅ |
| 3 | Murallas de Cartagena | Cartagena | ✅ |
| 4 | Baluarte Santo Domingo | Cartagena | ✅ |
| 5 | Fuerte de San Sebastián de Pastelillo | Cartagena | ⏳ |
| 6 | Castillo de Tequendama | Soacha | ⏳ |
| 7 | Fortaleza de Santa Cruz de Mompox | Mompox | ⏳ |
| 8 | Batería de San Mateo | Cartagena | ⏳ |
| 9 | Santuario de Las Lajas | Ipiales | ⏳ |
| 10 | Catedral de Sal de Zipaquirá | Zipaquirá | ⏳ |
| ... | ... | ... | ... |

---

## 6. Errores Comunes

| Error | Causa | Solución |
|-------|------|---------|
| `IMAGE(S) FAILED: Not Found` | URL no accesible | Buscar otra imagen |
| `IMAGE(S) FAILED: 404` | Imagen eliminada | Verificar URL manualmente |
| `IMAGE(S) FAILED: 429` | Rate limit | Esperar y retry |
| `isError: true` | Token inválido | Revisar MCP_TOKEN |
| `HTTP 400` | Faltan campos requeridos | Revisar JSON body |

---

## 7. Variables de Entorno (n8n)

```
MCP_TOKEN=tu_token_aqui
LISTEO_URL=https://xueturismo.com/wp-json/mcp-listeo/v1
```

---

## 8. Referencias

- **Documentación técnica**: `mcp-documentacion-tecnica.md`
- **Plan de carga**: `plan-colombia.md`
- **Scripts de búsqueda**: `search-images-*.js`
- **Plugin WordPress**: `mcp-listeo-connector/`

---

*Generado para n8n + ListeoMCP - xueturismo.com*