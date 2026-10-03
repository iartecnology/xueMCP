#!/usr/bin/env node

import fetch from "node-fetch";
import fs from "fs";

const WP_API_URL = process.env.LISTEO_URL || "https://xueturismo.com/wp-json/mcp-listeo/v1";
const MCP_TOKEN = process.env.MCP_TOKEN || "";

const postId = process.argv[2];
const jsonFile = process.argv[3];

if (!postId || !jsonFile) {
  console.error("Usage: node update.js <post-id> <json-file>");
  process.exit(1);
}

const data = JSON.parse(fs.readFileSync(jsonFile, "utf8"));
const listing = data[0];

console.log(`Updating Post ID: ${postId}`);

const response = await fetch(`${WP_API_URL}/call`, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-MCP-Token": MCP_TOKEN
  },
  body: JSON.stringify({
    ability: "wp/update-post",
    args: {
      post_id: parseInt(postId),
      ...listing.args
    }
  })
});

const result = await response.json();
console.log(JSON.stringify(result, null, 2));
