# Developer Guide: MCP Listeo Connector REST API

This guide explains how to interact with the WordPress MCP Connector from external services (Node.js, Python, or other AI orchestrators).

## 1. Authentication
All requests must be made via **HTTPS POST** and must include the security token in the headers.

**Header:** `X-MCP-Token`
**Value:** (Get this from the WordPress Admin > MCP Connector settings)

---

## 2. API Endpoint
The default REST endpoint is located at:
`https://yourdomain.com/wp-json/mcp/v1/call`

---

## 3. Request Structure
The request must be an application/json object with two main fields:
- `ability`: (string) The ID of the ability to execute.
- `args`: (object) The parameters for that specific ability.

### Example: Search for a Business (Google-like)
```json
{
  "ability": "wp/search-advanced",
  "args": {
    "query": "Hotel",
    "location": "Madrid",
    "max_price": 100,
    "features": ["wifi", "parking"],
    "limit": 5
  }
}
```

### Example: Create a Booking in Listeo
```json
{
  "ability": "listeo/create-booking",
  "args": {
    "listing_id": 123,
    "date": "2024-12-25",
    "time": "14:00",
    "comment": "Allergy to nuts"
  }
}
```

### Example: Update a Dokan Product
```json
{
  "ability": "dokan/manage-products",
  "args": {
    "action": "update",
    "product_id": 456,
    "price": "29.99",
    "stock_status": "instock"
  }
}
```

---

## 4. Response Structure
The plugin returns a standard JSON-RPC style response.

### Success Response (HTTP 200)
```json
{
  "success": true,
  "data": {
    "count": 1,
    "results": [
      {
        "id": 123,
        "title": "Hotel Ritz",
        "type": "listing",
        "link": "https://site.com/listing/hotel-ritz",
        "price": "90",
        "address": "Plaza de la Lealtad, 5"
      }
    ]
  }
}
```

### Error Response (HTTP 400/403/404)
```json
{
  "success": false,
  "data": "Invalid MCP Token."
}
```

---

## 5. Implementation Tips
- **Ability IDs**: You can find all registered IDs in the "Abilities" table within the WordPress admin dashboard.
- **Scope/Permissions**: If you get a "Permission denied" error, ensure the token's associated user (defined in code or by default admin) has the necessary WordPress capabilities (`read`, `publish_posts`, etc.).
- **Audit Logs**: Check the WordPress admin "Audit Logs" section to debug payload structures being sent from your external agent.
