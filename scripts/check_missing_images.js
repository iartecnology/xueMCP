import fetch from "node-fetch";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

async function checkListings() {
  console.log("🔍 Consultando listados para detectar falta de imágenes...");
  
  try {
    const response = await fetch(`${WP_API_URL}/call`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-MCP-Token": MCP_TOKEN,
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
      },
      body: JSON.stringify({
        ability: "listeo/list-listings",
        args: { limit: 60 }
      })
    });

    const result = await response.json();
    
    if (!result.data || !Array.isArray(result.data)) {
      console.log("❌ No se pudieron obtener los listados o la respuesta es inválida.");
      console.log(JSON.stringify(result, null, 2));
      return;
    }

    const missing = result.data.filter(item => !item.thumbnail || item.thumbnail.includes("placeholder"));
    
    console.log(`\n📊 Resultados:`);
    console.log(`✅ Total revisados: ${result.data.length}`);
    console.log(`⚠️ Sin imagen: ${missing.length}`);
    
    console.log("\n📋 Listado de IDs a corregir:");
    missing.forEach(item => {
      console.log(`ID: ${item.id} | Título: ${item.title}`);
    });

  } catch (error) {
    console.error(`❌ Error en la consulta: ${error.message}`);
  }
}

checkListings();
