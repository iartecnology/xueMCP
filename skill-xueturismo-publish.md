---
name: xueturismo-publish
description: Publicar listados en xueturismo.com con descripciones detalladas y orientadas al turista usando emojis.
---

# Publicación de Listados en xueturismo.com 📸✈️

Skill para crear y publicar listados (restaurantes, hoteles, puntos turísticos) en el sitio xueturismo.com usando el plugin MCP Listeo.

Este skill incluye:
- 🔍 Búsqueda de imágenes (DuckDuckGo, Bing, Google)
- ✍️ Descripciones detalladas y orientadas al turista
- 😊 Uso de emojis para mejorar la experiencia de lectura
- 🚀 Publicación directa via API REST

---

## Flujo de Trabajo

### 1. Preparar Datos del Listing

Crea un JSON con la estructura requerida:

```json
{
  "ability": "listeo/create-listing",
  "args": {
    "title": "Nombre del Lugar - Descripción Corta",
    "content": "<p>Contenido HTML con descripción completa...</p>",
    "keywords": "palabra1, palabra2, palabra3",
    "address": "Ciudad, Departamento, Colombia",
    "category": "restaurant|hotel|point_of_interest|tourist_attraction",
    "status": "publish",
    "image_urls": ["url1", "url2", "url3"]
  }
}
```

**Categorías válidas (xueturismo.com):**
- `turismo` - Lugares turísticos principales (260+ listados) ⭐ Recomendada
- `monumentos-historicos` - Monumentos, fortalezas, castillos
- `experiencias` - Experiencias turísticas
- `gastronomia` - Restaurantes y gastronomía
- `alojamiento` - Hoteles y alojamiento
- `ciudad-o-municipio` - Ciudades y municipios
- `eventos` - Eventos
- `point_of_interest` - Puntos de interés

### 2. Buscar Imágenes

El skill usa prioritariamente los buscadores que no requieren API keys:

#### Opción 1: DuckDuckGo (Recomendado - Sin API Key)

```bash
node /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/search-images-duckduckgo.js "query de búsqueda" [num_imágenes]
```

**Ejemplo:**
```bash
node /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/search-images-duckduckgo.js "Berlin Germany city landmarks" 5
```

#### Opción 2: Bing (Alternativo - Sin API Key)

```bash
node /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/search-images-bing.js "query de búsqueda" [num_imágenes]
```

#### Opción 3: Google (Requiere API Key)

```bash
node /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/search-images-google.js "query de búsqueda" [num_imágenes]
```

**Con credenciales configuradas:**
```bash
export GOOGLE_API_KEY=tu_api_key
export GOOGLE_CSE_ID=tu_search_engine_id
```

### 3. Publicar el Listing

#### Opción A: Publicación Directa (Si el servidor permite descargas externas)
```bash
node /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/publish.js <archivo-json>
```

#### Opción B: Método de Transferencia Local (Si hay bloqueos de firewall/Imunify360)
Si el servidor devuelve errores como `IMAGE(S) FAILED: Not Found` o `Forbidden`, usa este flujo:

1. **Descargar imagen localmente:**
   ```bash
   curl -L -A "Mozilla/5.0..." -o imagen_local.jpg "URL_DE_LA_IMAGEN"
   ```

2. **Subir imagen y JSON al servidor vía FTP:**
   Usa las credenciales de `iartecnology.com` para subir los archivos a `wp-content/plugins/mcp-listeo-connector/`.

3. **Ejecutar script de procesamiento local:**
   Crea y ejecuta un script PHP en el servidor que use `media_handle_sideload` con el archivo ya presente en el disco local del servidor. Esto evita que el servidor tenga que realizar una petición HTTP externa.

---

## Solución de Problemas de Imágenes

| Problema | Causa Probable | Solución |
|----------|----------------|----------|
| `FAILED: Not Found` | El servidor no llega a la URL | Usar Método de Transferencia Local |
| `FAILED: Forbidden` | Bloqueo por User-Agent | El plugin ya usa un User-Agent de Chrome, si falla, usa el método local |
| `No tienes permisos para subir este tipo de archivo` | Restricción de MIME type | Asegurarse de que la imagen sea un JPG/PNG válido antes de subir |

---

## Variables de Entorno Requeridas

Configura estas variables antes de publicar (Google API es opcional):

```bash
export LISTEO_URL="https://xueturismo.com/wp-json/mcp-listeo/v1"
export MCP_TOKEN="tu_token_de_acceso"

# Google API (opcional - solo si usas search-images-google.js)
# export GOOGLE_API_KEY="tu_google_api_key"
# export GOOGLE_CSE_ID="tu_search_engine_id"
```

**Nota:** El endpoint `/call` funciona sin token (permiso público).

---

## Estructuras Estandarizadas de Contenido HTML (Formato Obligatorio para Listeo)

> [!IMPORTANT]
> **REGLA DE FORMATO HTML EN LISTEO:**
> Para evitar que WordPress o el tema colapsen el texto en un solo párrafo plano, **todo contenido generado debe utilizar encabezados `<h3>`, separadores `<hr />`, párrafos `<p>` y listas con viñetas `<ul><li>`**. 
> Nunca uses `<p><strong>` como sustituto de títulos, pues se fusionan en el renderizado de la plantilla.

El campo `content` debe estructurarse según la tipología del lugar:

### 1. 🏛️ Sitios Turísticos y Monumentos
* **Título:** `[Emoji Tipo] [Nombre] — [Propuesta de Valor / Subtítulo] en [Municipio]`
* **Estructura HTML:**
```html
<h3>🎯 ¿Por qué visitarlo?</h3>
<p>[Párrafo introductorio de 50-70 palabras destacando la magia del lugar, su atractivo visual y por qué es una parada imperdible].</p>

<hr />

<h3>📜 Historia y Significado</h3>
<p>[Contexto histórico, origen del lugar, leyendas locales o datos curiosos explicados de forma amena].</p>

<hr />

<h3>🚶 Qué ver y hacer</h3>
<ul>
  <li><strong>📸 [Actividad / Hito 1]:</strong> [Breve descripción].</li>
  <li><strong>🧭 [Actividad / Hito 2]:</strong> [Breve descripción].</li>
  <li><strong>🌿 [Actividad / Hito 3]:</strong> [Breve descripción].</li>
</ul>

<hr />

<h3>⏰ Horarios y Tarifas</h3>
<p>[Días y horas de apertura, costos de entrada o si es libre y gratuito].</p>

<hr />

<h3>💡 Consejos para tu Visita</h3>
<ul>
  <li>☀️ <strong>Ropa y protección:</strong> [Recomendación de vestimenta, calzado y protección solar/clima].</li>
  <li>⏰ <strong>Mejor momento para ir:</strong> [Horas recomendadas para evitar multitudes o tomar mejores fotos].</li>
  <li>🌱 <strong>Cuidado y respeto:</strong> [Normas ecológicas o de convivencia patrimonial].</li>
</ul>

<hr />

<h3>🚗 Cómo llegar</h3>
<p>[Instrucciones claras desde el centro del municipio o vías principales, tipo de transporte y estado de la vía].</p>
```
* **Categorías:** `Turismo` (`167`), `Monumentos Históricos` (`1543`), `Atracción turística` (`1548`).

### 2. 🏨 Hoteles, Glampings y Alojamientos
* **Título:** `[Emoji Tipo] [Nombre del Hotel] — [Concepto de Hospedaje] en [Municipio]`
* **Estructura HTML:**
```html
<h3>✨ La Experiencia de Hospedaje</h3>
<p>[Descripción envolvente de la atmósfera: tranquilidad, lujo campestre o conexión con la naturaleza].</p>

<hr />

<h3>🛏️ Tipos de Habitaciones y Confort</h3>
<ul>
  <li><strong>🌟 [Tipo Habitación 1]:</strong> [Comodidades, vista, cama, baño privado].</li>
  <li><strong>🌿 [Tipo Habitación 2]:</strong> [Jacuzzi, chimenea, malla catamarán, etc.].</li>
</ul>

<hr />

<h3>🛎️ Servicios e Instalaciones</h3>
<ul>
  <li>🍳 <strong>Gastronomía:</strong> [Desayuno incluido, restaurante, etc.].</li>
  <li>📶 <strong>Conectividad y Parqueadero:</strong> [WiFi, estacionamiento vigilado].</li>
  <li>🔥 <strong>Zonas Comunes:</strong> [Fogatas, zonas verdes, spa, pet friendly].</li>
</ul>

<hr />

<h3>⏰ Horarios y Políticas de Reserva</h3>
<p><strong>Check-in:</strong> Desde las 03:00 p.m. | <strong>Check-out:</strong> Hasta las 12:00 m.</p>

<hr />

<h3>📍 Ubicación y Entorno</h3>
<p>[Distancia a la plaza principal y atractivos turísticos cercanos].</p>
```
* **Categorías:** `Alojamiento` (`31`), `Glamping` (`392`).

### 3. 🍽️ Restaurantes, Cafés y Gastronomía
* **Título:** `[Emoji Plato] [Nombre del Restaurante] — [Especialidad o Cocina] en [Municipio]`
* **Estructura HTML:**
```html
<h3>🍴 Concepto y Propuesta Culinaria</h3>
<p>[Concepto culinario: comida típica, parrilla o café de especialidad y ambiente].</p>

<hr />

<h3>🏆 Platos y Especialidades Recomendadas</h3>
<ul>
  <li>🍲 <strong>[Plato Estrella 1]:</strong> [Ingredientes y sabor característico].</li>
  <li>🥩 <strong>[Plato Estrella 2]:</strong> [Cortes o especialidad de la casa].</li>
  <li>🍰 <strong>[Postre o Bebida]:</strong> [Amasijos, dulces tradicionales o bebidas].</li>
</ul>

<hr />

<h3>🌿 Ambiente y Experiencia</h3>
<p>[Terraza, música, chimenea, apto para familias o mascotas].</p>

<hr />

<h3>⏰ Horarios y Rango de Precios</h3>
<p>Abierto de [Días y Horas]. Rango de precios: [$$ / $$$, ej. $25.000 - $60.000 COP].</p>

<hr />

<h3>📍 Dirección y Contacto</h3>
<p>[Ubicación exacta, referencias de llegada y reservas].</p>
```
* **Categorías:** `Gastronomía` (`21`), `Restaurante` (`1651`), `Sabores` (`1481`).

### 4. 🏙️ Municipios y Ciudades
* **Título:** `[Emoji Urbano] [Nombre Municipio] — [Identidad / Título Emblemático] en [Departamento]`
* **Estructura HTML:**
```html
<h3>🌟 Visión General y Encanto</h3>
<p>[Carácter del municipio, clima promedio, altitud y qué lo hace especial].</p>

<hr />

<h3>📜 Historia, Identidad y Cultura</h3>
<p>[Orígenes, fundación, raíces indígenas o coloniales y vocación de sus gentes].</p>

<hr />

<h3>🏛️ Principales Atractivos para Visitar</h3>
<ul>
  <li>🏛️ <strong>[Atractivo 1]:</strong> [Breve reseña].</li>
  <li>🌿 <strong>[Atractivo 2]:</strong> [Breve reseña].</li>
  <li>🏺 <strong>[Atractivo 3]:</strong> [Breve reseña].</li>
</ul>

<hr />

<h3>🍲 Gastronomía Tradicional y Sabores Locales</h3>
<p>[Platos típicos, amasijos, dulces o bebidas emblemáticas de la localidad].</p>

<hr />

<h3>🎉 Festividades Principales</h3>
<ul>
  <li>🎭 <strong>[Fiesta / Festival 1]:</strong> Celebrado en [Mes]. [Descripción breve].</li>
</ul>

<hr />

<h3>💡 Datos Prácticos para el Viajero</h3>
<ul>
  <li>🌡️ <strong>Clima:</strong> [Temperatura media y tipo de abrigo requerido].</li>
  <li>⏰ <strong>Tiempo recomendado:</strong> [1 día / fin de semana completo].</li>
</ul>

<hr />

<h3>🚗 Cómo llegar y Conectividad</h3>
<p>[Rutas de acceso terrestre desde Tunja/Bogotá y opciones de buses intermunicipales].</p>
```
* **Categoría:** `Ciudad o Municipio` (`584`).

---

### Guidelines Editoriales Generales

| Aspecto | Regla Obligatoria |
|---------|-------------------|
| **Estructura HTML** | Siempre usar `<h3>` para títulos de sección, `<hr />` como separador y `<ul><li>` para puntos destacados. |
| **Separación de Párrafos** | Párrafos envueltos en etiquetas `<p>...</p>` separadas, evitando bloques continuos sin respiración. |
| **Símbolos Monetarios** | Escribir `$10.000 COP` (asegurando el escape si se procesa en plantillas o scripts). |
| **Extensión** | 350-550 palabras (informativo, escaneable y sin relleno superfluo). |
| **Tono** | Cercano, entusiasta, útil y verificado para el turista. |
| **Metadatos Listeo** | Definir siempre `address`, `category`, `region`, `status: "publish"`. |

---

## Ejemplo Completo

1. **Crear JSON** (`mi-lugar.json`):
```json
{
  "ability": "listeo/create-listing",
  "args": {
    "title": "Restaurante El Colonial — Auténtica Cocina Boyacense en Villa de Leyva",
    "content": "<h3>🎯 ¿Por qué visitarlo?</h3>\n<p>El Colonial es el rincón gastronómico predilecto para los viajeros que buscan auténtico sabor criollo en una casona colonial del siglo XVIII.</p>\n\n<hr />\n\n<h3>🏆 Platos y Especialidades</h3>\n<ul>\n<li>🍲 <strong>Ajiaco Tradicional:</strong> Servido con mazorca tierna, alcaparras y crema de leche.</li>\n<li>🥩 <strong>Costillitas al Horno de Leña:</strong> Glaseadas con panela campesina y especias locales.</li>\n</ul>\n\n<hr />\n\n<h3>⏰ Horarios y Tarifas</h3>\n<p>Lunes a Domingo de 12:00 m. a 09:00 p.m. Platos desde $28.000 COP.</p>",
    "keywords": "restaurante Villa de Leyva, comida típica, cocina boyacense, centro histórico",
    "address": "Carrera 9 #12-34, Villa de Leyva, Boyacá, Colombia",
    "category": [21],
    "status": "publish",
    "image_urls": ["https://ejemplo.com/img1.jpg"]
  }
}
```

2. **Publicar**:
```bash
node /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/publish.js mi-lugar.json
```

---

## Actualizar Listings Existentes

Usa `update.js` en lugar de `publish.js`:

```bash
node /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/update.js <archivo-json>
```

El JSON debe incluir el `ID` del listing a actualizar.

---

## Errores Comunes

| Error | Solución |
|-------|----------|
| `401 Unauthorized` | Verificar MCP_TOKEN correcto |
| `404 Not Found` | Verificar LISTEO_URL correcta |
| `No images found` | Revisar query de búsqueda |
| `Invalid category` | Usar categoría válida de la lista |

---

## Archivos Relacionados

- scripts: `publish.js`, `update.js`
- búsqueda: `search-images-google.js`, `search-images-bing.js`, `search-images-duckduckgo.js`
- helper: `helpers/listeo-api.php`
- ejemplos: `SitiosPublicados/*.json`