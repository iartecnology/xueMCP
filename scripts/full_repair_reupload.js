import fetch from "node-fetch";
import fs from "fs";
import { execSync } from "child_process";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

const masterLog = JSON.parse(fs.readFileSync("listings/publicados/MASTER_LOG.json", "utf8"));
const repairList = JSON.parse(fs.readFileSync("temp_repair_list.json", "utf8"));

async function deletePost(id) {
    try {
        console.log(`🗑️ Borrando ID ${id}...`);
        const response = await fetch(`${WP_API_URL}/call`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
            body: JSON.stringify({
                ability: "wp/delete-post",
                args: { id: id, force: true }
            })
        });
        return await response.json();
    } catch (e) {
        return { isError: true, message: e.message };
    }
}

// Función para generar URL de Bing que el servidor acepte (JPG directo simulado)
function getBingUrl(title) {
    const q = encodeURIComponent(title + " colombia");
    return `https://th.bing.com/th/id/OIG.Search?q=${q}&w=1024&h=768&c=7&pid=Api`;
}

async function run() {
    console.log("🚀 Iniciando RE-CARGA MAESTRA...");

    for (const item of repairList) {
        if (item.title.toLowerCase().includes("borrador")) continue;

        // 1. Borrar el post actual
        await deletePost(item.id);

        // 2. Encontrar el archivo original en el log
        const logEntry = masterLog.find(l => l.id === item.id || l.title === item.title);
        if (logEntry && logEntry.file && fs.existsSync(logEntry.file)) {
            console.log(`📦 Procesando archivo: ${logEntry.file}`);
            
            let data = JSON.parse(fs.readFileSync(logEntry.file, "utf8"));
            if (!Array.isArray(data)) data = [data];

            // 3. Inyectar URL de Bing en el JSON
            for (let listing of data) {
                if (listing.args && listing.args.title === item.title) {
                    listing.args.image_urls = [getBingUrl(item.title)];
                    listing.args.status = "publish"; // Asegurar que se publique
                }
            }

            // Guardar temporalmente para subir
            const tempFile = "temp_reupload.json";
            fs.writeFileSync(tempFile, JSON.stringify(data, null, 2));

            // 4. Re-subir usando el script de publicación estándar
            try {
                console.log(`📤 Re-subiendo: ${item.title}`);
                execSync(`node scripts/publish.js ${tempFile}`, { stdio: "inherit" });
            } catch (e) {
                console.error(`❌ Error subiendo ${item.title}`);
            }
        } else {
            console.log(`⚠️ No se encontró el archivo local para: ${item.title}`);
        }
        
        await new Promise(r => setTimeout(r, 1000));
    }

    console.log("\n🏁 RE-CARGA FINALIZADA.");
}

run();
