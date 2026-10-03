#!/usr/bin/env node

import fetch from "node-fetch";
import fs from "fs";
import path from "path";
import { execSync } from "child_process";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

const jsonFile = process.argv[2];

if (!jsonFile) {
  console.error("Usage: node publish.js <json-file>");
  process.exit(1);
}

const absolutePath = path.resolve(jsonFile);
let data = JSON.parse(fs.readFileSync(absolutePath, "utf8"));

// Support both single object and array of objects
if (!Array.isArray(data)) {
  data = [data];
}

let successCount = 0;

for (const listing of data) {
  if (!listing.args || !listing.args.title) {
    console.error("❌ Invalid listing structure. Skipping...");
    continue;
  }

  console.log(`\n🚀 Publishing: ${listing.args.title}`);

  try {
    const response = await fetch(`${WP_API_URL}/call`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-MCP-Token": MCP_TOKEN
      },
      body: JSON.stringify({
        ability: listing.ability,
        args: listing.args
      })
    });

    const result = await response.json();
    console.log(JSON.stringify(result, null, 2));

    if (result.data && result.data.id) {
      console.log(`✅ Published! Post ID: ${result.data.id}`);
      console.log(`🔗 URL: ${result.data.link}`);
      
      // Save the ID back to the listing object in our local data
      listing.args.id = result.data.id;
      listing.args.link = result.data.link;
      successCount++;
    } else {
      console.error("❌ Publish failed or response unclear.");
    }
  } catch (error) {
    console.error(`❌ Error publishing: ${error.message}`);
  }
}

// Save updated data with IDs back to the file
if (successCount > 0) {
  fs.writeFileSync(absolutePath, JSON.stringify(data, null, 2));
}

// Post-publish automation
if (successCount > 0) {
  const publishedDir = path.join(process.cwd(), "listings", "publicados");
  const fileName = path.basename(absolutePath);
  const destPath = path.join(publishedDir, fileName);

  // Don't move if it's already in the published directory
  if (absolutePath !== destPath) {
    console.log(`\n📦 Moving ${fileName} to listings/publicados/...`);
    fs.renameSync(absolutePath, destPath);
  }

  console.log("📊 Updating MASTER_LOG.json...");
  try {
    execSync("node scripts/generate_master_log.js", { stdio: "inherit" });
  } catch (e) {
    console.error("❌ Failed to update master log.");
  }
}
