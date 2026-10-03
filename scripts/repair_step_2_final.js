import fetch from "node-fetch";
import fs from "fs";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

// Mapeo manual o búsqueda sugerida para asegurar calidad en lugar de búsqueda aleatoria
const repairList = JSON.parse(fs.readFileSync("temp_repair_list.json", "utf8"));

// Función para obtener URL de imagen de Bing (simulada con fuentes estables basadas en el título)
// En un entorno real, aquí se llamaría a una API de búsqueda.
// Para este fix, usaré una lógica de "Bing Search URL" que el conector pueda descargar.
function getBingImageUrl(title) {
    const query = encodeURIComponent(title + " colombia");
    // Usamos el servicio de imágenes de Bing th.bing.com que es muy estable
    // Generamos una búsqueda que el servidor pueda procesar
    return `https://th.bing.com/th/id/OIG.Search?q=${query}&w=1024&h=768&c=7&pid=Api`;
}

async function updateListing(id, imageUrl) {
    try {
        console.log(`🚀 Reparando ID ${id}: Enviando imagen...`);
        const response = await fetch(`${WP_API_URL}/call`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
            body: JSON.stringify({
                ability: "listeo/update-listing",
                args: {
                    id: id,
                    image_url: imageUrl
                }
            })
        });
        const result = await response.json();
        return result.data;
    } catch (e) {
        console.error(`❌ Error en ID ${id}: ${e.message}`);
        return null;
    }
}

async function run() {
    console.log(`🛠️ Iniciando REPARACIÓN MASIVA de ${repairList.length} items...`);
    
    for (const item of repairList) {
        // Ignorar "Borradores automáticos"
        if (item.title.toLowerCase().includes("borrador")) continue;

        const imageUrl = getBingImageUrl(item.title);
        const result = await updateListing(item.id, imageUrl);
        
        if (result && result.image_status === "success") {
            console.log(`✅ ID ${item.id} (${item.title}) REPARADO.`);
        } else {
            console.log(`⚠️ ID ${item.id} falló: ${result ? result.message : "Error desconocido"}`);
        }
        
        // Pequeña pausa para no saturar el servidor
        await new Promise(resolve => setTimeout(resolve, 1000));
    }
    console.log("\n🏁 Proceso de reparación finalizado.");
}

run();
