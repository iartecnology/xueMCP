# Documentación Técnica: MCP Listeo Connector

**Sitio**: `https://xueturismo.com`  
**Plugin**: `mcp-listeo-connector v1.0.0`  
**Estado**: ✅ Activo en Producción  
**Última verificación**: 2026-04-13  

---

## 1. Configuración de Conectividad

### Endpoints Disponibles

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET`  | `/wp-json/mcp-listeo/v1/tools` | Lista todas las abilities activas con su schema |
| `POST` | `/wp-json/mcp-listeo/v1/call`  | Ejecuta una ability |
| `POST` | `/wp-json/mcp-listeo/v1/chat`  | Chat con el agente IA integrado |
| `POST` | `/wp-json/mcp-listeo/v1/auto-create` | Publicación automática con generación IA |

### Autenticación

La seguridad se configura desde el panel de administración de WordPress (`MCP Connector > Configuración`).

**Opción 1 — Token en Header** (Recomendado para producción):
```http
POST /wp-json/mcp-listeo/v1/call
X-MCP-Token: {tu-token-secreto}
Content-Type: application/json
```

**Opción 2 — Security Bypass** (Solo para desarrollo):
```
WordPress Admin > MCP Connector > Seguridad > Deshabilitar validación: ON
```
> [!CAUTION]
> El bypass de seguridad solo debe usarse en entornos de prueba o redes privadas.

---

## 2. Estructura de una Petición

```http
POST https://xueturismo.com/wp-json/mcp-listeo/v1/call
Content-Type: application/json

{
  "ability": "<grupo>/<nombre-de-la-ability>",
  "args": {
    "parametro_1": "valor",
    "parametro_2": 123
  }
}
```

### Estructura de la Respuesta

**Éxito**:
```json
{
  "isError": false,
  "data": { "...resultado de la ability..." }
}
```

**Error**:
```json
{
  "isError": true,
  "message": "Descripción exacta del error",
  "code": 400
}
```

---

## 3. Referencia Completa de Abilities

> Estado verificado en producción consultando `GET /wp-json/mcp-listeo/v1/tools`

### 3.1 `wp/get-post`
Obtiene detalles de cualquier post, página o CPT por ID o slug.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `id` | number | ✗ | ID del post en WordPress |
| `slug` | string | ✗ | Slug URL del post |
| `post_type` | string | ✗ | Tipo de post. Default: `"any"` |

**Ejemplo**:
```json
{
  "ability": "wp/get-post",
  "args": { "id": 4701 }
}
```

---

### 3.2 `wp/create-post`
Crea una nueva entrada, página o CPT genérico.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `title` | string | ✅ | Título del post |
| `content` | string | ✗ | Contenido HTML |
| `status` | string | ✗ | `publish`, `draft`, `pending`. Default: `draft` |
| `post_type` | string | ✗ | Tipo de post. Default: `post` |
| `author` | number | ✗ | ID de usuario autor |

**Ejemplo**:
```json
{
  "ability": "wp/create-post",
  "args": {
    "title": "Nuevo artículo de blog",
    "content": "<p>Contenido del artículo.</p>",
    "status": "publish"
  }
}
```

---

### 3.3 `wp/update-post`
Actualiza título, contenido o estado de un post existente.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `id` | number | ✅ | ID del post a actualizar |
| `title` | string | ✗ | Nuevo título |
| `content` | string | ✗ | Nuevo contenido HTML |
| `status` | string | ✗ | Nuevo estado |

**Ejemplo**:
```json
{
  "ability": "wp/update-post",
  "args": {
    "id": 4701,
    "title": "Título actualizado",
    "status": "publish"
  }
}
```

---

### 3.4 `wp/search-advanced`
Búsqueda experta cruzando Listings, Productos y Posts con filtros geográficos, de precio y características.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `query` | string | ✗ | Texto de búsqueda |
| `location` | string | ✗ | Ciudad o región (ej: `"Bogotá"`) |
| `min_price` | number | ✗ | Precio mínimo |
| `max_price` | number | ✗ | Precio máximo |
| `features` | array | ✗ | Lista de features (ej: `["wifi", "parking"]`) |
| `is_open` | boolean | ✗ | Filtrar solo lugares abiertos ahora |
| `min_rating` | number | ✗ | Calificación mínima (1-5) |

**Ejemplo**:
```json
{
  "ability": "wp/search-advanced",
  "args": {
    "query": "hotel con piscina",
    "location": "Cartagena",
    "max_price": 200,
    "min_rating": 4
  }
}
```

---

### 3.5 `listeo/get-listings`
Obtiene listados del directorio con filtros por categoría, región o palabras clave.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `category` | string | ✗ | Slug, ID o nombre de categoría |
| `region` | string | ✗ | Slug, ID o nombre de región (ej: `"Tunja"`) |
| `search` | string | ✗ | Búsqueda por texto en título o descripción |
| `limit` | number | ✗ | Resultados máximos. Default: `10` |

**Respuesta incluye**: `id`, `title`, `address`, `location (lat/lng)`, `price`, `thumbnail`, `link`

**Ejemplo**:
```json
{
  "ability": "listeo/get-listings",
  "args": {
    "region": "Bogotá",
    "search": "restaurante",
    "limit": 5
  }
}
```

---

### 3.6 `listeo/get-listing-details`
Obtiene todos los metadatos, reseñas y galería de un anuncio específico.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `id` | number | ✅ | ID del listing |

**Ejemplo**:
```json
{
  "ability": "listeo/get-listing-details",
  "args": { "id": 4706 }
}
```

---

### 3.7 `listeo/create-listing` ⭐ Principal
Crea un nuevo anuncio en el directorio con imagen, mapa y metadatos completos.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `title` | string | ✅ | Nombre del lugar |
| `content` | string | ✗ | Descripción completa (acepta HTML) |
| `status` | string | ✗ | `publish`, `draft`, `pending`. Default: `pending` |
| `address` | string | ✗ | Dirección física completa |
| `lat` | number | ✗ | Latitud GPS (decimal) |
| `lng` | number | ✗ | Longitud GPS (decimal) |
| `price` | string | ✗ | Rango de precios (ej: `"50€ - 200€"`) |
| `keywords` | string | ✗ | Palabras clave SEO separadas por coma |
| `image_url` | string | ✗ | URL directa de imagen (Unsplash recomendado) |
| `category` | array[number] | ✗ | IDs de categorías Listeo |
| `region` | array[number] | ✗ | IDs de regiones |
| `type` | string | ✗ | Tipo de listing |

> [!IMPORTANT]
> `image_url` debe ser una URL directa a un archivo de imagen (`.jpg`, `.png`, `.webp`).  
> Proveedor recomendado: `https://images.unsplash.com/{photo-id}?auto=format&fit=crop&q=80&w=1200`

**Ejemplo completo con imagen**:
```json
{
  "ability": "listeo/create-listing",
  "args": {
    "title": "Machu Picchu - La Ciudad Perdida de los Incas",
    "content": "Enclavada en las montañas andinas del Perú, Machu Picchu es una de las maravillas más impresionantes del mundo. Esta ciudadela inca del siglo XV, declarada Patrimonio de la Humanidad por la UNESCO, ofrece vistas espectaculares y una experiencia histórica única.",
    "address": "08680 Machu Picchu, Cusco, Perú",
    "lat": -13.1631,
    "lng": -72.5450,
    "price": "50€ - 150€",
    "keywords": "perú, machu picchu, incas, patrimonio, maravilla, andes",
    "image_url": "https://images.unsplash.com/photo-1526392060635-9d6019884377?auto=format&fit=crop&q=80&w=1200",
    "status": "publish"
  }
}
```

**Respuesta**:
```json
{
  "isError": false,
  "data": {
    "id": 4709,
    "link": "https://xueturismo.com/-/machu-picchu-la-ciudad-perdida-de-los-incas/",
    "message": "Listing created successfully WITH IMAGE.",
    "image_status": "success",
    "image_error": ""
  }
}
```

---

### 3.8 `listeo/create-booking`
Envía una petición de reserva en un anuncio.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `listing_id` | number | ✅ | ID del listing a reservar |
| `date` | string | ✗ | Fecha en formato `YYYY-MM-DD` |
| `people` | number | ✗ | Número de personas |

**Ejemplo**:
```json
{
  "ability": "listeo/create-booking",
  "args": {
    "listing_id": 4706,
    "date": "2026-05-20",
    "people": 2
  }
}
```

---

### 3.9 `listeo/get-listing-meta`
Recupera todos los metadatos crudos de WordPress para un anuncio (útil para debug).

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `id` | integer | ✅ | ID del listing |

**Ejemplo**:
```json
{
  "ability": "listeo/get-listing-meta",
  "args": { "id": 4706 }
}
```

---

### 3.10 `listeo/get-user-dashboard-data`
Obtiene estadísticas del panel del usuario autenticado: listings activos, reservas y reseñas.

**Parámetros**: Ninguno requerido.

**Ejemplo**:
```json
{
  "ability": "listeo/get-user-dashboard-data",
  "args": {}
}
```

---

### 3.11 `listeo/get-user-messages`
Recupera los hilos de mensajes privados del sistema de mensajería de Listeo.

**Parámetros**: Ninguno requerido.

**Ejemplo**:
```json
{
  "ability": "listeo/get-user-messages",
  "args": {}
}
```

---

### 3.12 `dokan/get-vendor-store`
Obtiene los detalles de la tienda de un vendedor: nombre, URL, banner y ubicación.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `vendor_id` | number | ✅ | ID del vendedor en WordPress |

**Ejemplo**:
```json
{
  "ability": "dokan/get-vendor-store",
  "args": { "vendor_id": 15 }
}
```

---

### 3.13 `dokan/create-product`
Crea un producto WooCommerce asignado a un vendedor específico.

**Parámetros**:
| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `vendor_id` | number | ✅ | ID del vendedor |
| `name` | string | ✅ | Nombre del producto |
| `description` | string | ✗ | Descripción del producto |
| `price` | number | ✗ | Precio en la moneda del sitio |

**Ejemplo**:
```json
{
  "ability": "dokan/create-product",
  "args": {
    "vendor_id": 15,
    "name": "Tour Guiado por el Centro Histórico",
    "description": "Recorrido de 3 horas por los principales monumentos.",
    "price": 45
  }
}
```

---

## 4. Códigos de Error

| Código | Causa | Acción |
|--------|-------|--------|
| `400` | Parámetro obligatorio faltante o inválido | Revisar los campos `required` del schema |
| `403` | Token inválido o usuario sin permisos | Verificar `X-MCP-Token` en los ajustes del plugin |
| `404` | Ability no encontrada | Verificar el ID en `GET /tools` |
| `500` | Error interno del servidor | Revisar el log de WordPress (`debug.log`) |

---

## 5. Ejemplos de Integración

### cURL (Shell)
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"ability":"listeo/get-listings","args":{"limit":3}}' \
  https://xueturismo.com/wp-json/mcp-listeo/v1/call
```

### JavaScript / Fetch
```javascript
const response = await fetch('https://xueturismo.com/wp-json/mcp-listeo/v1/call', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    ability: 'listeo/create-listing',
    args: {
      title: 'Mi Nuevo Destino',
      address: 'Calle 123, Bogotá, Colombia',
      lat: 4.7110,
      lng: -74.0721,
      status: 'publish'
    }
  })
});
const data = await response.json();
console.log(data.data.link); // URL pública del listing
```

### Python
```python
import requests

payload = {
    "ability": "listeo/get-listings",
    "args": { "region": "Cartagena", "limit": 5 }
}

resp = requests.post(
    "https://xueturismo.com/wp-json/mcp-listeo/v1/call",
    json=payload
)

listings = resp.json()["data"]
for l in listings:
    print(l["title"], "→", l["link"])
```

### n8n (HTTP Request Node)
```
Method:  POST
URL:     https://xueturismo.com/wp-json/mcp-listeo/v1/call
Headers: Content-Type: application/json
Body:    { "ability": "listeo/get-listings", "args": { "limit": 10 } }
```

---

## 6. Imágenes de Referencia (Unsplash)

Para `image_url`, usar el patrón:
```
https://images.unsplash.com/{PHOTO_ID}?auto=format&fit=crop&q=80&w=1200
```

| Destino | Photo ID |
|---------|----------|
| Roma / Coliseo | `photo-1552832230-c0197dd311b5` |
| París / Torre Eiffel | `photo-1511739001486-6bfe10ce785f` |
| Tokio | `photo-1540959733332-eab4deabeeaf` |
| Machu Picchu | `photo-1526392060635-9d6019884377` |
| Nueva York | `photo-1534430480872-3498386e7856` |
| Santorini / Grecia | `photo-1533105079780-92b9be482077` |
| Bali / Indonesia | `photo-1537996194471-e657df975ab4` |
| Safari / África | `photo-1516426122078-c23e76319801` |

---

## 7. Verificar Estado del Sistema

```bash
# Ver todas las abilities activas
curl -s https://xueturismo.com/wp-json/mcp-listeo/v1/tools | python3 -m json.tool | grep '"name"'

# Publicación de prueba rápida
curl -X POST -H "Content-Type: application/json" \
  -d '{"ability":"listeo/create-listing","args":{"title":"Test","status":"draft"}}' \
  https://xueturismo.com/wp-json/mcp-listeo/v1/call
```

---

*MCP Listeo Connector — xueturismo.com*  
*Desarrollado por Antigravity para Listeo + Dokan + WordPress*
