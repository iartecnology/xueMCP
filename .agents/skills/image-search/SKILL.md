---
name: image-search
description: >-
  Search, evaluate, download and attach real, high-resolution and authentic images
  from Google/Web search and open image indexes for tourism listings, dishes, attractions and blog posts.
  Activate this skill whenever the user asks to find images for listings, publications, dishes,
  municipalities, attractions or events, or when an automated publishing routine needs images.
---

# 📸 Image Search & Media Pipeline Skill

Esta skill permite buscar y descargar imágenes reales, en alta resolución y sin marcas de agua de atractivos turísticos, platos típicos, festividades y hoteles para las publicaciones de **XUÉ Turismo (Listeo)**.

---

## 🚀 Cómo ejecutar la búsqueda de imágenes

La skill cuenta con un motor multi-fuente optimizado (Web Image Index + Wikimedia Commons) accesible directamente por terminal:

### 1. Búsqueda directa (Devuelve JSON con URLs originales y dimensiones):
```bash
python3 /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/.agents/skills/image-search/scripts/search_images.py "<término de búsqueda>" --limit 5
```

### 2. Búsqueda y Descarga Automática a un directorio local:
```bash
python3 /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/.agents/skills/image-search/scripts/search_images.py "<término de búsqueda>" --limit 3 --download /tmp/images
```

---

## 📋 Reglas de Calidad para Imágenes de Publicación

1. **Resolución:**
   * Mínimo preferido: `800x600` px.
   * Evitar miniaturas (*thumbnails* con dimensiones inferiores a `400x300`).
2. **Autenticidad:**
   * Las fotos de platos deben mostrar el plato servido o en preparación tradicional (evitar fotos de stock genéricas de otros países).
   * Las fotos de municipios/atractivos deben corresponder exactamente a la plaza, templo o parque especificado.
3. **Flujo de Asignación en WordPress / Listeo:**
   * Cuando se asigne a un listado (`listing`), actualizar tanto `featured_image` (`_thumbnail_id`) como la galería `_gallery` para asegurar que la tarjeta del catálogo y la página individual coincidan.
   * Si el servidor externo bloquea descargas directas (Hotlinking o 429), descargar localmente a `/tmp/` y subir vía FTP a `xueturismo.com/wp-content/uploads/2026/09/`.
