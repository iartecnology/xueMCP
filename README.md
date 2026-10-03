# XUÉ MCP Server (`xuemcp`)

Servidor y conector MCP (Model Context Protocol) para **XUÉ Turismo** y WordPress / Listeo. Permite a agentes de IA interactuar con listados, gastronomía, imágenes, taxonomías y contenidos turísticos de forma autónoma.

## Features

- **Get Listings**: List all listings with search and filter support.
- **Listing Details**: Retrieve full JSON of a listing including custom meta (coordinates, address).
- **Taxonomies**: Access categories, regions, and features.

## Installation

1. Clone or copy the folder into your local environment.
2. Install dependencies:
   ```bash
   npm install
   ```
3. Set your site URL as an environment variable if it's different from the default:
   ```bash
   export LISTEO_URL=https://yoursite.com/wp-json/wp/v2
   ```

## Configuration for Claude Desktop / Anthropic

Add this to your `claude_desktop_config.json`:

Add this to your `claude_desktop_config.json` or Antigravity/Cursor MCP settings:

```json
{
  "mcpServers": {
    "listeo": {
      "command": "node",
      "args": ["/Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/scripts/index.js"],
      "env": {
        "LISTEO_URL": "https://xueturismo.com/wp-json/mcp-listeo/v1",
        "MCP_TOKEN": "your_mcp_security_token"
      }
    }
  }
}
```

## Available Abilities & Tools (Dynamic)

- **`listeo/audit-listings`**: Health check scanner to identify missing cover/gallery photos, GPS map coordinates, opening hours, or contact info.
- **`listeo/list-bookings`**: Search and inspect customer reservations by status and listing ID.
- **`listeo/update-booking-status`**: Approve, cancel, or mark reservations as paid.
- **`listeo/create-booking`**: Create booking requests in pending/draft status.
- **`listeo/create-listing`**: Insert new directory listings with enriched tourist metadata, coordinates, prices, and photo galleries.
- **`listeo/update-listing`**: Edit existing listings with new descriptions, opening hours, social profiles, and categories.
- **`listeo/get-listings`**: Query and filter directory items.
- **`listeo/get-user-dashboard`**: User statistics and listing performance.

## Developed with 💙 by Antigravity
Analyzed core theme structures, custom post types (`listing`), and REST API integration points.
