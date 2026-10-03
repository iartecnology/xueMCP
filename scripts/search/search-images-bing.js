#!/usr/bin/env node
/**
 * Bing Image Search for ListeoMCP
 * Usa Bing para encontrar imágenes
 * 
 * Usage: node search-images-bing.js "search query" [num_results]
 */

import https from 'https';
import http from 'http';

const query = process.argv[2];
const numResults = parseInt(process.argv[3]) || 3;

if (!query) {
  console.error('❌ Usage: node search-images-bing.js "search query" [num_results]');
  process.exit(1);
}

console.log(`🔍 Bing Images Search: "${query}"`);
console.log(`📊 Results: ${numResults}\n`);

const searchBingImages = async (query, numResults) => {
  try {
    // Step 1: Get Bing search page to extract tokens
    const url = `https://www.bing.com/images/search?q=${encodeURIComponent(query)}&first=1&count=${numResults}`;
    
    const html = await makeRequest(url, {
      'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language': 'en-US,en;q=0.9',
    });

    // Extract image URLs from HTML using regex
    const imgRegex = /https:\/\/[^"'\s]+\.(jpg|jpeg|png|webp)/gi;
    const matches = [];
    let match;
    
    while ((match = imgRegex.exec(html)) !== null) {
      const url = match[0];
      // Filter out thumbnails and small images
      if (!url.includes('thumb') && !url.includes('thumbnail') && !url.includes('small') && url.includes('http')) {
        matches.push(url);
      }
    }

    // Remove duplicates
    const uniqueImages = [...new Set(matches)].slice(0, numResults);

    if (uniqueImages.length === 0) {
      console.log('⚠️ No images found');
      return;
    }

    console.log(`✅ Found ${uniqueImages.length} images:\n`);
    console.log('='.repeat(100));

    uniqueImages.forEach((imgUrl, index) => {
      console.log(`\n📸 Image ${index + 1}:`);
      console.log(`   URL: ${imgUrl}`);
      console.log('='.repeat(100));
    });

    console.log('\n📋 JSON format:');
    const jsonOutput = uniqueImages.map(url => ({ image_url: url }));
    console.log(JSON.stringify(jsonOutput, null, 2));

  } catch (error) {
    console.error('❌ Error:', error.message);
    process.exit(1);
  }
};

const makeRequest = (url, headers) => {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url);
    const protocol = urlObj.protocol === 'https:' ? https : http;

    const options = {
      hostname: urlObj.hostname,
      path: urlObj.pathname + urlObj.search,
      method: 'GET',
      headers: headers,
    };

    const req = protocol.request(options, (res) => {
      let data = '';

      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        resolve(makeRequest(res.headers.location, headers));
        return;
      }

      res.on('data', (chunk) => {
        data += chunk;
      });

      res.on('end', () => {
        if (res.statusCode >= 200 && res.statusCode < 300) {
          resolve(data);
        } else {
          reject(new Error(`HTTP ${res.statusCode}: ${data.substring(0, 200)}`));
        }
      });
    });

    req.on('error', reject);
    req.end();
  });
};

searchBingImages(query, numResults);
