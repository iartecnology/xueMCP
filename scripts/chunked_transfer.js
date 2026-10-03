import fetch from "node-fetch";
import fs from "fs";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

async function uploadInChunks(url, filename) {
    console.log(`🌍 Descargando: ${url}`);
    const response = await fetch(url, { headers: { "User-Agent": "Mozilla/5.0" } });
    const buffer = await response.buffer();
    
    // 1. Borrar si existe borrando y creando el archivo vacío
    await fetch(`${WP_API_URL}/call`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
        body: JSON.stringify({ ability: "utils/upload-file", args: { filename: filename, base64_content: Buffer.from("").toString("base64") } })
    });

    const chunkSize = 20 * 1024; // 20KB
    for (let i = 0; i < buffer.length; i += chunkSize) {
        const chunk = buffer.slice(i, i + chunkSize);
        console.log(`📤 Subiendo chunk ${i/chunkSize + 1}... (${chunk.length} bytes)`);
        await fetch(`${WP_API_URL}/call`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
            body: JSON.stringify({
                ability: "utils/upload-chunk",
                args: { filename: filename, base64_chunk: chunk.toString('base64') }
            })
        });
    }
    return filename;
}

async function run() {
    const title = "🍛 Bandeja Paisa — El Festín Montañero de Antioquia";
    const imageUrl = "https://i.pinimg.com/originals/f0/54/2e/f0542e3995813338309df50d32152869.jpg";
    
    await uploadInChunks(imageUrl, "bandeja_paisa_final.jpg");
    
    // Crear listado
    console.log(`🚀 Creando listado con imagen local...`);
    const res = await fetch(`${WP_API_URL}/call`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
        body: JSON.stringify({
            ability: "listeo/create-listing",
            args: {
                title: title,
                content: "La Bandeja Paisa es el alma de la gastronomía antioqueña.",
                image_urls: ["bandeja_paisa_final.jpg"], // El helper buscará esto en mcp-temp
                status: "publish",
                category: [28]
            }
        })
    });
    console.log(await res.text());
}

run();
