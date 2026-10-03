import fetch from "node-fetch";
import fs from "fs";
import { execSync } from "child_process";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

const filesToReupload = [
    "listings/publicados/pacifico-parques-01-utria.json",
    "listings/publicados/pacifico-parques-02-katios.json",
    "listings/publicados/termales-02-san-vicente.json",
    "listings/publicados/termales-03-ruiz.json",
    "listings/publicados/termales-04-paipa.json",
    "listings/publicados/termales-05-coconuco.json",
    "listings/publicados/rios-01-magdalena.json",
    "listings/publicados/rios-02-cauca.json",
    "listings/publicados/rios-03-guatape.json",
    "listings/publicados/sabores-28-bandeja-paisa.json",
    "listings/publicados/sabores-29-mondongo.json",
    "listings/publicados/sabores-30-fritanga.json",
    "listings/publicados/sabores-31-cazuela.json",
    "listings/publicados/sabores-32-arroz-pollo.json",
    "listings/publicados/sabores-batch-29.json",
    "listings/publicados/sabores-batch-30.json",
    "listings/publicados/sabores-batch-31.json",
    "listings/publicados/aventura-batch-33.json",
    "listings/publicados/trekking-batch-34.json"
];

async function deleteListingByTitle(title) {
    try {
        // Buscar el ID por título
        const searchRes = await fetch(`${WP_API_URL}/call`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
            body: JSON.stringify({ ability: "listeo/list-listings", args: { search: title, limit: 1 } })
        });
        const searchData = await searchRes.json();
        
        if (searchData.data && searchData.data.length > 0) {
            const id = searchData.data[0].id;
            console.log(`🗑️ Borrando existente: "${title}" (ID: ${id})`);
            await fetch(`${WP_API_URL}/call`, {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
                body: JSON.stringify({ ability: "wp/delete-post", args: { id: id, force: true } })
            });
        }
    } catch (e) {
        console.error(`⚠️ Error al intentar borrar "${title}": ${e.message}`);
    }
}

async function run() {
    console.log("🔥 INICIANDO RE-CARGA AUTOMÁTICA MASIVA...");

    for (const file of filesToReupload) {
        if (!fs.existsSync(file)) {
            console.log(`⏩ Saltando ${file} (no existe)`);
            continue;
        }

        console.log(`\n📦 PROCESANDO ARCHIVO: ${file}`);
        let listings = JSON.parse(fs.readFileSync(file, "utf8"));
        if (!Array.isArray(listings)) listings = [listings];

        for (const item of listings) {
            if (item.args && item.args.title) {
                // Borrar versiones previas fallidas
                await deleteListingByTitle(item.args.title);
            }
        }

        // Re-publicar el archivo original
        try {
            console.log(`📤 Cargando contenido original de ${file}...`);
            execSync(`node scripts/publish.js ${file}`, { stdio: "inherit" });
        } catch (e) {
            console.error(`❌ Falló la publicación de ${file}`);
        }
        
        await new Promise(r => setTimeout(r, 2000));
    }

    console.log("\n✅ PROCESO COMPLETADO EXITOSAMENTE.");
}

run();
