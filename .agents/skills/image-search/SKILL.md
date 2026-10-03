---
name: image-search
description: >-
  Search, evaluate, download and attach real, high-resolution and authentic images
  from Web Image Index (DuckDuckGo, Bing) and open image indexes (Wikimedia Commons)
  for tourism listings, dishes, attractions and blog posts.
  Activate this skill whenever the user asks to find images for listings, publications, dishes,
  municipalities, attractions or events, or when an automated publishing routine needs images.
---

# 📸 Image Search & Media Pipeline Skill

Esta skill dota a cualquier agente de Antigravity/AGY de la capacidad autónoma para buscar, filtrar y descargar fotografías reales, en alta resolución y sin marcas de agua de atractivos turísticos, platos típicos, municipios y alojamientos para las publicaciones de **XUÉ Turismo (Listeo)**.

---

## ⚡ Autoconfiguración y Descubrimiento para Agentes

Cualquier agente que ejecute tareas en este repositorio o que cargue este plugin descubrirá y utilizará esta skill automáticamente gracias a su ubicación en:
* **Workspace (Repo):** `.agents/skills/image-search/SKILL.md`
* **Global Plugins:** `~/.gemini/config/plugins/image-search-plugin/`

### Capacidades del Motor de Búsqueda Integrado:
1. **Multi-Engine Robusto con Fallback Automático:**
   * **DuckDuckGo:** Acceso directo a imágenes a resolución completa sin necesidad de API key ni cuotas.
   * **Bing Async:** Respaldo automático de alta disponibilidad si el primer motor está restringido.
   * **Wikimedia Commons:** Fuente abierta y verificada para monumentos históricos, plazas, parques nacionales e iglesias patrimoniales.
2. **Filtro Anti-Marcas de Agua:**
   * Exclusión automática de bancos de fotos comerciales con marcas de agua intrusivas (`shutterstock`, `alamy`, `gettyimages`, etc.).
3. **Descarga y Conversión Nativas:**
   * Soporte automático para descargar con cabeceras `Referer`/`User-Agent` correctas y almacenar en formatos estándar (`.jpg`, `.png`, `.webp`).

---

## 🚀 Guía de Uso Rápido por Terminal

### 1. Búsqueda y Obtención de URLs (JSON):
```bash
python3 .agents/skills/image-search/scripts/search_images.py "<término de búsqueda>" --limit 5
```

### 2. Búsqueda y Descarga Directa a una Carpeta Local:
```bash
python3 .agents/skills/image-search/scripts/search_images.py "<término de búsqueda>" --limit 3 --download /tmp/images
```

---

## 📋 Reglas de Calidad Obligatorias para Publicación

1. **Resolución:**
   * Preferida: `1200x800` o superior (mínimo admisible: `800x600`).
   * Descartar miniaturas o imágenes comprimidas de baja fidelidad.
2. **Autenticidad del Objeto / Destino:**
   * **Platos típicos:** Deben mostrar el plato servido o en preparación tradicional de la región (evitar fotos genéricas de otros países).
   * **Municipios y Atractivos:** Deben reflejar fielmente la plaza, templo, mirador o arquitectura del lugar específico.
3. **Flujo de Asignación en WordPress / Listeo:**
   * Actualizar siempre tanto `featured_image` (`_thumbnail_id`) como la galería `_gallery` para evitar discrepancias entre el catálogo y la página del listado.
   * Si el servidor externo bloquea descargas directas (*hotlinking* o 429), descargar localmente a `/tmp/` y subir vía FTP a `xueturismo.com/wp-content/uploads/2026/09/`.
