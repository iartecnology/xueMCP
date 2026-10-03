# Guía del Desarrollador: MCP Listeo Connector (Español)

Esta guía detalla cómo utilizar el conector MCP para WordPress, Listeo y Dokan mediante llamadas REST API.

## 1. Configuración de Conectividad
Todas las peticiones son de tipo **POST** y requieren autenticación mediante un Token de seguridad.

- **URL del Endpoint**: `https://xueturismo.com/wp-json/mcp-listeo/v1/call`
- **Header Requerido**: `X-MCP-Token` (Obtenlo en el panel de administración de WordPress > MCP Connector).

---

## 2. Listado Completo de Habilidades (Abilities)

### A. Descubrimiento e IA (WordPress Core)

#### `wp/search-advanced` (Búsqueda Inteligente Experimental)
Busca en Listados, Productos y Entradas con filtros avanzados.
```json
{
  "ability": "wp/search-advanced",
  "args": {
    "query": "Hotel con piscina",
    "location": "Madrid",
    "features": ["wifi", "parking"],
    "max_price": 100,
    "limit": 5
  }
}
```

#### `wp/get-post`
Obtiene los detalles de cualquier entrada, página o CPT específico.
```json
{
  "ability": "wp/get-post",
  "args": {
    "id": 123,
    "post_type": "listing"
  }
}
```

#### `wp/create-post`
Crea una entrada o página básica en WordPress.
```json
{
  "ability": "wp/create-post",
  "args": {
    "title": "Nueva noticia",
    "content": "Contenido de la noticia...",
    "status": "publish"
  }
}
```

#### `wp/update-post`
Actualiza una entrada o página existente.
```json
{
  "ability": "wp/update-post",
  "args": {
    "id": 123,
    "title": "Título actualizado",
    "status": "publish"
  }
}
```

---

### B. Gestión de Listeo (Directorio y Reservas)

#### `listeo/create-booking`
Realiza una reserva en un listado específico.
```json
{
  "ability": "listeo/create-booking",
  "args": {
    "listing_id": 50,
    "date": "2024-12-25",
    "time": "20:00",
    "comment": "Mesa cerca de la ventana"
  }
}
```

#### `listeo/create-listing`
Crea un nuevo listado en el directorio (requiere permisos de Dueño/Owner).
```json
{
  "ability": "listeo/create-listing",
  "args": {
    "title": "Restaurante La Pausa",
    "address": "Calle Mayor 1, Madrid",
    "price": "30-50",
    "category": [12]
  }
}
```

#### `listeo/get-user-messages`
Recupera los mensajes privados del usuario autenticado.
```json
{
  "ability": "listeo/get-user-messages",
  "args": {
    "limit": 10
  }
}
```

#### `listeo/get-user-dashboard`
Obtiene estadísticas del panel de control del usuario (listados activos, vistas, reservas pendientes).
```json
{
  "ability": "listeo/get-user-dashboard",
  "args": {}
}
```

---

### C. Gestión de Dokan (Marketplace Multivendedor)

#### `dokan/manage-products`
Crea o edita productos en la tienda del vendedor.
```json
{
  "ability": "dokan/manage-products",
  "args": {
    "action": "create",
    "name": "Camiseta Orgánica",
    "price": "25.00",
    "description": "Camiseta de algodón 100% orgánico."
  }
}
```

#### `dokan/manage-store`
Actualiza la configuración de la tienda del vendedor (nombre, dirección, social).
```json
{
  "ability": "dokan/manage-store",
  "args": {
    "store_name": "Mi Tienda Ecológica",
    "social": {
      "facebook": "https://facebook.com/mitienda"
    }
  }
}
```

---

## 3. Estructura de Respuesta

### Éxito (Código 200)
```json
{
  "success": true,
  "data": { ... resultado de la habilidad ... }
}
```

### Error (Código 400, 403, 404)
```json
{
  "success": false,
  "data": "Descripción del error (ej: Token inválido o Permiso denegado)"
}
```

---

## 4. Notas de Seguridad
- El sistema utiliza `current_user_can()` internamente. La petición se ejecuta en el contexto del usuario que generó el token o el administrador por defecto.
- Se recomienda rotar el token desde el panel de WP si sospechas que ha sido filtrado.
