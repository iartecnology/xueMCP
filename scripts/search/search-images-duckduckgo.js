#!/usr/bin/env node
/**
 * DuckDuckGo Image Search for ListeoMCP
 * 
 * Usage: node search-images-duckduckgo.js "search query" [num_results]
 * Example: node search-images-duckduckgo.js "Cartagena Colombia beach" 10
 */

import https from 'https';
import http from 'http';

const query = process.argv[2];
const numResults = parseInt(process.argv[3]) || 3;

if (!query) {
  console.error('❌ Usage: node search-images-duckduckgo.js "search query" [num_results]');
  console.error('Example: node search-images-duckduckgo.js "Cartagena Colombia beach" 10');
  process.exit(1);
}

console.log(`🔍 Searching: "${query}"`);
console.log(`📊 Results: ${numResults}\n`);

// DuckDuckGo Image Search via Instant Answer API
const searchDuckDuckGo = async (query, numResults) => {
  try {
    // Step 1: Get VQD from DuckDuckGo HTML
    const vqdToken = await getVQD(query);
    
    if (!vqdToken) {
      throw new Error('Could not get VQD token from DuckDuckGo');
    }
    
    console.log('✅ Got VQD token:', vqdToken, '\n');
    
    // Step 2: Search images using the image.js endpoint
    const results = await searchImages(query, vqdToken, numResults);
    
    if (!results || results.length === 0) {
      console.log('⚠️ No images found');
      return;
    }
    
    console.log(`✅ Found ${results.length} images:\n`);
    console.log('='.repeat(100));
    
    results.forEach((img, index) => {
      console.log(`\n📸 Image ${index + 1}:`);
      console.log(`   URL: ${img.image}`);
      console.log(`   Source: ${img.url}`);
      console.log(`   Title: ${img.title || 'N/A'}`);
      console.log(`   Width: ${img.width} | Height: ${img.height}`);
      console.log('='.repeat(100));
    });
    
    // Output as JSON for easy integration
    console.log('\n📋 JSON format for ListeoMCP:');
    const jsonOutput = results.map(img => ({
      image_url: img.image,
      source_url: img.url,
      title: img.title,
      width: img.width,
      height: img.height,
    }));
    
    console.log(JSON.stringify(jsonOutput, null, 2));
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    console.error('Stack:', error.stack);
    process.exit(1);
  }
};

// Get VQD token from DuckDuckGo
const getVQD = async (query) => {
  const url = `https://duckduckgo.com/html/?q=${encodeURIComponent(query)}`;
  
  const html = await makeRequest(url, {
    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language': 'en-US,en;q=0.9',
  });
  
  // Extract VQD from HTML - multiple patterns
  const patterns = [
    /vqd=['"]([^'"]+)['"]/,
    /vqd=([^&"'"]+)/,
    /name="vqd"\s+value="([^"]+)"/,
  ];
  
  for (const pattern of patterns) {
    const match = html.match(pattern);
    if (match) {
      return match[1];
    }
  }
  
  // Debug: save HTML to file if needed
  // require('fs').writeFileSync('debug.html', html);
  console.log('⚠️ HTML snippet (first 500 chars):', html.substring(0, 500));
  
  return null;
};

// Search images using DuckDuckGo i.js endpoint
const searchImages = async (query, vqdToken, numResults) => {
  const url = `https://duckduckgo.com/i.js?q=${encodeURIComponent(query)}&vqd=${vqdToken}&o=json&f=1&l=wt-wt`;
  
  const data = await makeRequest(url, {
    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'application/json',
    'Referer': 'https://duckduckgo.com/',
  });
  
  const parsed = JSON.parse(data);
  
  if (!parsed.results || parsed.results.length === 0) {
    return [];
  }
  
  return parsed.results.slice(0, numResults);
};

// Helper to make HTTP/HTTPS requests
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
      
      // Follow redirects
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

searchDuckDuckGo(query, numResults);
