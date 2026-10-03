import fetch from "node-fetch";
import fs from "fs";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

const repairList = JSON.parse(fs.readFileSync("temp_repair_list.json", "utf8"));

// Usamos Unsplash Source que es mucho más estable para descargas automáticas
function getImageUrl(title) {
    const query = encodeURIComponent(title.replace(/[^\w\s]/gi, '') + " colombia");
    // Source Unsplash es ideal para scripts de automatización
    return `https://source.unsplash.com/1600x900/?${query}`;
}

async function updateListing(id, imageUrl) {
    try {
        const response = await fetch(`${WP_API_URL}/call`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
            body: JSON.stringify({
                ability: "listeo/update-listing",
                args: { id: id, image_url: imageUrl }
            }),
            timeout: 60000 // 60 seconds timeout
        });
        const result = await response.json();
        return result;
    } catch (e) {
        return { isError: true, message: e.message };
    }
}

async function run() {
    console.log(`🛠️ Reparando ${repairList.length} items...`);
    
    for (let i = 0; i < repairList.length; i++) {
        const item = repairList[i];
        if (item.title.toLowerCase().includes("borrador")) continue;
        if (item.title.toLowerCase().includes("prueba")) continue;

        console.log(`[${i+1}/${repairList.length}] 🚀 ${item.title} (ID: ${item.id})...`);
        const imageUrl = getImageUrl(item.title);
        const res = await updateListing(item.id, imageUrl);
        
        if (res && res.data && res.data.image_status === "success") {
            console.log(`   ✅ REPARADO.`);
        } else {
            console.log(`   ❌ ERROR: ${res.data ? res.data.image_status : (res.message || "Falla de red")}`);
        }
        
        await new Promise(r => setTimeout(r, 2000));
    }
    console.log("\n🏁 Reparación finalizada.");
}

run();
