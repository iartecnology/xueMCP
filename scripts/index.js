#!/usr/bin/env node
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";
import fetch from "node-fetch";
import { z } from "zod";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";

/**
 * Listeo MCP Server
 * Conecta Perplexity o Claude Desktop con tu directorio Listeo.
 */
class ListeoServer {
  constructor() {
    this.server = new Server(
      {
        name: "listeo-connector",
        version: "1.1.0",
      },
      {
        capabilities: {
          tools: {},
        },
      }
    );

    this.setupToolHandlers();
    
    // Error handling
    this.server.onerror = (error) => console.error("[MCP Error]", error);
    process.on('SIGINT', async () => {
      await this.server.close();
      process.exit(0);
    });
  }

  async fetchFromWP(endpoint, params = {}) {
    const fullUrl = `${WP_API_URL.replace(/\/$/, '')}${endpoint}`;
    const url = new URL(fullUrl);
    
    // Añadimos parámetros y cache buster
    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
    url.searchParams.append('_cb', Date.now()); 

    const token = process.env.MCP_TOKEN || process.env.WP_AUTH;
    const headers = { 
        'Accept': 'application/json',
        'X-MCP-Token': token
    };

    if (process.env.WP_AUTH && process.env.WP_AUTH.includes(':')) {
        const encoded = Buffer.from(process.env.WP_AUTH).toString('base64');
        headers['Authorization'] = `Basic ${encoded}`;
    } else if (process.env.WP_AUTH && !process.env.MCP_TOKEN) {
        // Si solo hay WP_AUTH y no tiene :, lo usamos como token
        headers['X-MCP-Token'] = process.env.WP_AUTH;
    }

    console.error(`[Listeo] Fetching: ${url.toString()}`);
    
    try {
        const response = await fetch(url.toString(), { headers });
        
        if (!response.ok) {
            const errorBody = await response.text();
            console.error(`[Listeo] Error ${response.status}: ${errorBody.substring(0, 100)}`);
            throw new Error(`WordPress API error: ${response.status} ${response.statusText}`);
        }
        
        return await response.json();
    } catch (err) {
        console.error(`[Listeo] Fetch Failed: ${err.message}`);
        throw err;
    }
  }

  setupToolHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      try {
        const result = await this.fetchFromWP("/tools");
        return {
          tools: result.tools || []
        };
      } catch (error) {
        console.error("Error fetching tools:", error);
        return { tools: [] };
      }
    });

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        // Enrutamos dinámicamente cualquier herramienta al endpoint /call de WordPress
        // asumiendo que el nombre de la herramienta es el ID de la 'ability' en WP
        const result = await fetch(`${WP_API_URL}/call`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-MCP-Token": process.env.MCP_TOKEN || process.env.WP_AUTH // Usamos token de seguridad
            },
            body: JSON.stringify({
                ability: name,
                args: args
            })
        });

        if (!result.ok) {
            const errorData = await result.json();
            throw new Error(errorData.message || `HTTP ${result.status}`);
        }

        const data = await result.json();
        
        // El plugin de WordPress devuelve el resultado directamente en 'data' o en la raíz
        const output = data.data !== undefined ? data.data : data;

        return {
          content: [{ type: "text", text: typeof output === 'string' ? output : JSON.stringify(output, null, 2) }]
        };

      } catch (error) {
        return {
          content: [{ type: "text", text: `Error ejecutando ${name}: ${error.message}` }],
          isError: true
        };
      }
    });
  }

  // Eliminamos el switch case estático ya que ahora es dinámico

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error("Listeo MCP Server running on stdio");
    console.error("Configured URL:", WP_API_URL);
  }
}

const server = new ListeoServer();
server.run();
