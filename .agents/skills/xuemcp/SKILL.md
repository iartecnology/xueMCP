---
name: xuemcp
description: >-
  Consolidated skill for XUÉ Turismo (xueturismo.com / Listeo). Manages end-to-end publishing,
  content curation, listing updates, quality audit, and multi-engine image search (DuckDuckGo, Bing, Wikimedia).
  Activate this skill whenever the user asks to search images, create or update listings (restaurants, hotels, attractions,
  municipalities, gastronomic dishes), audit content, or publish blog posts on xueturismo.com.
---

# 🚀 XUÉ MCP — Comprehensive Publishing & Media Skill

Esta skill dota a cualquier agente de Antigravity/AGY de la capacidad completa y autónoma para:
1. **🔍 Buscar y descargar imágenes de alta resolución sin marcas de agua** mediante un pipeline multi-motor (DuckDuckGo, Bing async, Wikimedia Commons).
2. **✍️ Redactar y formatear contenido turístico estandarizado** según las directrices de diseño de Listeo/WordPress (encabezados `<h3>`, separadores `<hr />`, listas `<ul><li>`, emojis temáticos y descripciones orientadas al viajero).
3. **🏛️ Publicar y gestionar listados en xueturismo.com** respetando rigurosamente la taxonomía oficial (IDs de categorías, regiones y tipos de publicación).
4. **🛠️ Manejar pipelines de subida y contingencia** (API REST directa, sideload local y sincronización vía FTP).

---

## ⚡ Autoconfiguración y Descubrimiento para Agentes

Cualquier agente que trabaje en este entorno o cargue este plugin descubrirá y utilizará esta skill automáticamente gracias a su ubicación estándar:
* **Workspace (Repo):** `.agents/skills/xuemcp/SKILL.md`
* **Global Plugins:** `~/.gemini/config/plugins/xuemcp-plugin/skills/xuemcp/SKILL.md`
* **URL GitHub para Autoconfiguración de Agentes Externos:**
  `https://github.com/iartecnology/xuemcp/tree/main/.agents/skills/xuemcp`

---

## 📸 Módulo 1: Pipeline de Búsqueda y Descarga de Imágenes

El motor de búsqueda integrado elimina la necesidad de tokens externos y cuenta con fallback automático.

### Características del Motor:
- **DuckDuckGo:** Acceso directo a imágenes en resolución completa.
- **Bing Async:** Respaldo automático de alta disponibilidad si DuckDuckGo es limitado.
- **Wikimedia Commons:** Fuente abierta y verificada para monumentos históricos, plazas, parques e iglesias patrimoniales.
- **Filtro Anti-Stock:** Excluye sitios con marcas de agua comerciales (`shutterstock`, `alamy`, `gettyimages`, `istockphoto`, etc.).

### Comandos de Terminal:

#### 1. Obtener lista de URLs en formato JSON:
```bash
python3 .agents/skills/xuemcp/scripts/search_images.py "<término de búsqueda>" --limit 5
```

#### 2. Descargar imágenes directamente a carpeta local:
```bash
python3 .agents/skills/xuemcp/scripts/search_images.py "<término de búsqueda>" --limit 3 --download /tmp/images
```

#### 3. Reglas de Calidad Obligatorias:
- **Resolución:** Preferida `1200x800` o superior (mínimo admisible `800x600`).
- **Autenticidad:**
  - *Platos típicos:* Deben mostrar el plato servido o en preparación tradicional de la región (evitar fotos genéricas de otros países).
  - *Municipios y Atractivos:* Deben reflejar fielmente la plaza, templo, mirador o arquitectura del lugar específico.
- **Asignación en WordPress / Listeo:**
  - Asignar siempre `featured_image` (`_thumbnail_id`) y la galería `_gallery`.

---

## 📝 Módulo 2: Estructuras HTML Estandarizadas (Formato Listeo)

> [!IMPORTANT]
> **REGLA DE ORO DE LISTEO:**
> Para evitar que WordPress colapse el texto en un solo párrafo continuo, **todo contenido debe usar encabezados `<h3>`, separadores `<hr />`, párrafos `<p>` y listas `<ul><li>`**.
> NUNCA uses `<p><strong>` como sustituto de títulos.

---

### 1. 🏛️ Sitios Turísticos y Monumentos
* **Título:** `[Emoji Tipo] [Nombre] — [Propuesta de Valor / Subtítulo] en [Municipio]`
* **Plantilla HTML:**
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
* **Taxonomía:** Categorías `Turismo` (`167`), `Monumentos Históricos` (`1543`), `Atracción turística` (`1548`).

---

### 2. 🏨 Hoteles, Glampings y Alojamientos
* **Título:** `[Emoji Tipo] [Nombre del Hotel] — [Concepto de Hospedaje] en [Municipio]`
* **Plantilla HTML:**
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
* **Taxonomía:** Categorías `Alojamiento` (`31`), `Glamping` (`392`).

---

### 3. 🍽️ Restaurantes y Gastronomía Comercial
* **Título:** `[Emoji Plato] [Nombre del Restaurante] — [Especialidad o Cocina] en [Municipio]`
* **Plantilla HTML:**
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
* **Taxonomía OBLIGATORIA:** Categorías `Gastronomía` (`21`), `Restaurante` (`1651`).
  *(ADVERTENCIA: NO usar categoría `1481` para restaurantes comerciales).*

---

### 4. 🍲 Platos Típicos y Sabores Culturales (Fichas de Gastronomía)
* **Título:** `🍲 [Nombre del Plato] — [Identidad / Sazón Típica] de [Región/Municipio]`
* **Plantilla HTML:**
```html
<h3>🍲 Tradición y Origen</h3>
<p>[Historia del plato tradicional, importancia cultural y festividades donde se consume].</p>

<hr />

<h3>🥄 Ingredientes Auténticos y Preparación</h3>
<ul>
  <li>🌽 <strong>[Ingrediente Base]:</strong> [Descripción].</li>
  <li>🥩 <strong>[Carnes / Especias]:</strong> [Descripción].</li>
  <li>🔥 <strong>[Cocción tradicional]:</strong> [Olla de barro, leña, etc.].</li>
</ul>

<hr />

<h3>📍 Dónde Probarlo</h3>
<p>[Plazas de mercado emblemáticas, pueblos reconocidos por este plato y restaurantes tradicionales].</p>
```
* **Taxonomía OBLIGATORIA:** Categoría `Sabores` (`1481`).

---

### 5. 🏙️ Municipios y Ciudades
* **Título:** `[Emoji Urbano] [Nombre Municipio] — [Identidad / Título Emblemático] en [Departamento]`
* **Plantilla HTML:**
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
<p>[Rutas de acceso terrestre y opciones de transporte].</p>
```
* **Taxonomía:** Categoría `Ciudad o Municipio` (`584`).

---

## 📊 Módulo 3: Matriz de Taxonomías y Regiones

### Categorías Principales (`listing_category`):
| Slug / Concepto | ID | Uso Exclusivo |
|---|---|---|
| `turismo` | `167` | Sitios turísticos, miradores y atractivos |
| `monumentos-historicos` | `1543` | Museos, patrimonio, puentes y templos históricos |
| `atraccion-turistica` | `1548` | Parques temáticos, ecoparques y reservas |
| `alojamiento` | `31` | Hoteles, hostales, fincas y cabañas |
| `glamping` | `392` | Glampings y domos ecológicos |
| `gastronomia` | `21` | Gastronomía general |
| `restaurante` | `1651` | Establecimientos comerciales de comida |
| `sabores` | `1481` | Platos típicos culturales (NO restaurantes) |
| `ciudad-o-municipio` | `584` | Guías de ciudades y municipios |
| `noticias` (Blog) | `107` | Artículos editoriales (Post Type: `post`) |

### Regiones / Departamentos (`region`):
| Región / Departamento | ID |
|---|---|
| Boyacá | `121` |
| Cundinamarca | `398` |
| Santander | `404` |
| Antioquia | `405` |
| Caldas / Eje Cafetero | `407` |
| Tolima | `408` |
| Huila | `409` |
| Meta | `410` |
| Casanare | `411` |

---

## 🚀 Módulo 4: Publicación, Edición y Eliminación vía API REST / MCP (Sin Contraseñas de Aplicación)

> [!TIP]
> **Autenticación sin Contraseñas:**
> El endpoint `/call` de `mcp-listeo` está diseñado para operar de forma nativa sin requerir *Application Passwords* ni autenticación Basic de WordPress.
> Internamente, el conector eleva el contexto del proceso para ejecutar las operaciones con permisos administrativos seguros.

### 1. Creación de Listing (`listeo/create-listing`):
Ejemplo con payload JSON:
```json
{
  "ability": "listeo/create-listing",
  "args": {
    "title": "Catedral Basílica Metropolitana — Tesoro Colonial en Tunja",
    "content": "<h3>🎯 ¿Por qué visitarla?</h3><p>...</p><hr /><h3>📜 Historia</h3><p>...</p>",
    "keywords": "catedral tunja, turismo boyaca, arquitectura colonial",
    "address": "Plaza de Bolívar, Tunja, Boyacá, Colombia",
    "category": [167, 1543],
    "region": [121],
    "status": "publish",
    "image_urls": ["https://ejemplo.com/catedral-tunja.jpg"]
  }
}
```

Llamada directa vía cURL / Python:
```bash
curl -X POST "https://xueturismo.com/wp-json/mcp-listeo/v1/call" \
  -H "Content-Type: application/json" \
  -d '{"ability": "listeo/create-listing", "args": {"title": "Nombre", "status": "publish"}}'
```

### 2. Actualización de Listing (`listeo/update-listing`):
```json
{
  "ability": "listeo/update-listing",
  "args": {
    "id": 12345,
    "title": "Nuevo Título Optimizado",
    "category": [21, 1651],
    "image_urls": ["https://ejemplo.com/nueva-imagen.jpg"]
  }
}
```

### 3. Eliminación de Listing o Post (`wp/delete-post`):
Para enviar a la papelera o eliminar permanentemente un listado o entrada sin contraseñas:
```json
{
  "ability": "wp/delete-post",
  "args": {
    "id": 12345,
    "force": true
  }
}
```

Llamada directa vía cURL:
```bash
curl -X POST "https://xueturismo.com/wp-json/mcp-listeo/v1/call" \
  -H "Content-Type: application/json" \
  -d '{"ability": "wp/delete-post", "args": {"id": 12345, "force": true}}'
```
*(Usa `"force": false` para mover a la papelera o `"force": true` para eliminación definitiva).*

### 4. Contingencia de Red y Subida de Archivos:
- **Sandbox Network:** Si hay problemas de resolución DNS hacia `xueturismo.com`, dirigir tráfico a la IP directa `69.10.33.22` con cabecera `Host: xueturismo.com`.
- **Hotlinking Bloqueado:** Si la descarga externa en WordPress falla, descargar la imagen con `python3 search_images.py --download /tmp/images`, transferir vía FTP a `/xueturismo.com/wp-content/uploads/2026/09/` y asociar con script de sideload local.

---

## 📋 Módulo 5: Gestión y Sincronización del Inventario Maestro

El repositorio cuenta con el archivo maestro [`sitios_publicados.md`](file:///Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/sitios_publicados.md), el cual contiene la totalidad de los listados en vivo con sus IDs oficiales, enlaces directos y categorías asignadas.

### 1. Auditoría y Prevención de Duplicados:
Antes de redactar o publicar un nuevo atractivo, restaurante, hotel o plato típico, el agente **debe consultar `sitios_publicados.md`** para verificar si ya existe un listado previo o si requiere una actualización de contenido/imágenes en lugar de crear un duplicado.

### 2. Sincronización Automática del Inventario:
El agente dispone del script automatizado para consultar la API REST de WordPress y refrescar el inventario maestro con todas las páginas activas:

```bash
python3 .agents/skills/xuemcp/scripts/sync_inventory.py
```

Este script:
- Consulta de forma paginada `https://xueturismo.com/wp-json/wp/v2/listing`.
- Extrae el ID, título limpio, enlace canónico y categorías numéricas de cada publicación.
- Actualiza la cabecera con la fecha y hora exacta de sincronización y el total en vivo.

