import fetch from "node-fetch";
import fs from "fs";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

async function getDetails(id) {
    try {
        const response = await fetch(`${WP_API_URL}/call`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
            body: JSON.stringify({
                ability: "listeo/get-listing-details",
                args: { id: id }
            })
        });
        const result = await response.json();
        return result.data;
    } catch (e) {
        return null;
    }
}

async function run() {
    console.log("🔍 Iniciando escaneo de IDs 6275 a 6342...");
    const results = [];
    for (let id = 6275; id <= 6342; id++) {
        const data = await getDetails(id);
        if (data && data.title) {
            const hasImage = data.thumbnail && !data.thumbnail.includes("placeholder");
            console.log(`[${id}] ${data.title} - ${hasImage ? "✅ TIENE IMAGEN" : "❌ SIN IMAGEN"}`);
            if (!hasImage) {
                results.push({ id: id, title: data.title });
            }
        }
    }
    fs.writeFileSync("temp_repair_list.json", JSON.stringify(results, null, 2));
    console.log(`\n📊 Escaneo finalizado. ${results.length} listados necesitan reparación.`);
}

run();
