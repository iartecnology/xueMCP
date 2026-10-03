import fetch from "node-fetch";
import fs from "fs";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

async function downloadAndUpload(url, filename) {
    console.log(`🌍 Descargando: ${url}`);
    const response = await fetch(url, {
        headers: { "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36" }
    });
    const buffer = await response.buffer();
    const base64Content = buffer.toString('base64');
    console.log(`📦 Contenido Base64 preparado (${buffer.length} bytes)`);

    console.log(`📤 Subiendo a WordPress vía MCP...`);
    const uploadRes = await fetch(`${WP_API_URL}/call`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
        body: JSON.stringify({
            ability: "utils/upload-file",
            args: {
                filename: filename,
                base64_content: base64Content
            }
        })
    });
    const uploadData = await uploadRes.json();
    return uploadData;
}

async function run() {
    // Ejemplo con Bandeja Paisa
    const imageUrl = "https://www.antojandoando.com/wp-content/uploads/2014/01/bandeja_paisa2.jpg";
    const res = await downloadAndUpload(imageUrl, "bandeja-paisa-real.jpg");
    console.log(JSON.stringify(res, null, 2));
}

run();
