import fetch from "node-fetch";
import fs from "fs";
import { execSync } from "child_process";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

async function run() {
    const title = "🍛 Bandeja Paisa — El Festín Montañero de Antioquia";
    const imageUrl = "https://upload.wikimedia.org/wikipedia/commons/9/9b/Bandeja_paisa_%285082434401%29.jpg";

    console.log(`🚀 Probando re-carga de: ${title}`);

    // 1. Borrar actual
    const searchRes = await fetch(`${WP_API_URL}/call`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
        body: JSON.stringify({ ability: "listeo/list-listings", args: { search: title, limit: 1 } })
    });
    const searchData = await searchRes.json();
    if (searchData.data && searchData.data.length > 0) {
        await fetch(`${WP_API_URL}/call`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
            body: JSON.stringify({ ability: "wp/delete-post", args: { id: searchData.data[0].id, force: true } })
        });
    }

    // 2. Crear nuevo con la imagen
    const createData = [{
        ability: "listeo/create-listing",
        args: {
            title: title,
            content: "La Bandeja Paisa es el plato más representativo de la región de Antioquia y el Eje Cafetero. Es un festín generoso que incluye frijoles, arroz, carne molida, chicharrón, huevo frito, tajada de plátano, chorizo, arepa y aguacate.",
            image_urls: [imageUrl],
            status: "publish",
            category: [28] // Sabores
        }
    }];
    fs.writeFileSync("temp_paisa.json", JSON.stringify(createData, null, 2));
    execSync("node scripts/publish.js temp_paisa.json", { stdio: "inherit" });
}

run();
