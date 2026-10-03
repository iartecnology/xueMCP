import fetch from "node-fetch";
import fs from "fs";
import { execSync } from "child_process";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";
const masterLog = JSON.parse(fs.readFileSync("listings/publicados/MASTER_LOG.json", "utf8"));
const batch = JSON.parse(fs.readFileSync("temp_repair_batch_1.json", "utf8"));

async function deletePost(id) {
    console.log(`🗑️ Borrando ID ${id}...`);
    await fetch(`${WP_API_URL}/call`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-MCP-Token": MCP_TOKEN },
        body: JSON.stringify({ ability: "wp/delete-post", args: { id: id, force: true } })
    });
}

async function run() {
    for (const item of batch) {
        await deletePost(item.id);
        const logEntry = masterLog.find(l => l.title === item.title);
        if (logEntry && fs.existsSync(logEntry.file)) {
            let data = JSON.parse(fs.readFileSync(logEntry.file, "utf8"));
            if (!Array.isArray(data)) data = [data];
            for (let listing of data) {
                if (listing.args && listing.args.title === item.title) {
                    listing.args.image_urls = [item.new_image];
                    listing.args.status = "publish";
                }
            }
            fs.writeFileSync("temp_reupload_batch.json", JSON.stringify(data, null, 2));
            console.log(`📤 Re-subiendo con fuente real: ${item.title}`);
            execSync(`node scripts/publish.js temp_reupload_batch.json`, { stdio: "inherit" });
        }
        await new Promise(r => setTimeout(r, 1000));
    }
}
run();
