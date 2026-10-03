#!/usr/bin/env node
/**
 * Google Images Search for ListeoMCP
 * 
 * Usa Google Custom Search API (gratuita hasta 100 búsquedas/día)
 * 
 * Configuración requerida:
 * 1. API Key: https://developers.google.com/custom-search/v1/overview
 * 2. Search Engine ID (CX): https://cse.google.com/cse/all (configurar para buscar imágenes)
 * 
 * Variables de entorno:
 *   GOOGLE_API_KEY=your_api_key
 *   GOOGLE_CSE_ID=your_search_engine_id
 * 
 * Usage: node search-images-google.js "search query" [num_results]
 * Example: node search-images-google.js "Cartagena Colombia beach" 3
 */

import https from 'https';
import http from 'http';

const query = process.argv[2];
const numResults = parseInt(process.argv[3]) || 3;

if (!query) {
  console.error('❌ Usage: node search-images-google.js "search query" [num_results]');
  console.error('Example: node search-images-google.js "Cartagena Colombia beach" 3');
  process.exit(1);
}

console.log(`🔍 Google Images Search: "${query}"`);
console.log(`📊 Results: ${numResults}\n`);

const searchGoogleImages = async (query, numResults) => {
  try {
    const apiKey = process.env.GOOGLE_API_KEY;
    const cseId = process.env.GOOGLE_CSE_ID;
    
    if (!apiKey || !cseId) {
      console.error('⚠️ Faltan credenciales de Google API');
      console.error('');
      console.error('📋 Configuración requerida:');
      console.error('1. Obtén API Key en: https://developers.google.com/custom-search/v1/overview');
      console.error('2. Crea Search Engine en: https://cse.google.com/cse/all');
      console.error('   - Selecciona "Search the entire web"');
      console.error('   - En "Image search" activa "Search images only"');
      console.error('3. Guarda las credenciales:');
      console.error('   export GOOGLE_API_KEY=tu_api_key');
      console.error('   export GOOGLE_CSE_ID=tu_search_engine_id');
      console.error('');
      console.error('💡 Alternativa rápida:');
      console.error('   Busca manualmente en images.google.com');
      console.error('   Copia las URLs de las imágenes que te gusten');
      console.error('   Y úsalas directamente en el JSON del listing');
      return;
    }
    
    // Google Custom Search API
    const url = `https://www.googleapis.com/customsearch/v1?q=${encodeURIComponent(query)}&cx=${cseId}&key=${apiKey}&searchType=image&num=${Math.min(numResults, 10)}&imgSize=large`;
    
    const response = await makeRequest(url, {
      'Accept': 'application/json',
    });
    
    const data = JSON.parse(response);
    
    if (!data.items || data.items.length === 0) {
      console.log('⚠️ No se encontraron imágenes');
      return;
    }
    
    const results = data.items.slice(0, numResults);
    
    console.log(`✅ Found ${results.length} images:\n`);
    console.log('='.repeat(100));
    
    results.forEach((img, index) => {
      console.log(`\n📸 Image ${index + 1}:`);
      console.log(`   URL: ${img.link}`);
      console.log(`   Source: ${img.image.contextLink}`);
      console.log(`   Title: ${img.title || 'N/A'}`);
      console.log(`   Size: ${img.image.width}x${img.image.height}`);
      console.log('='.repeat(100));
    });
    
    // Output as JSON for easy integration
    console.log('\n📋 JSON format for ListeoMCP:');
    const jsonOutput = results.map(img => ({
      image_url: img.link,
      source_url: img.image.contextLink,
      title: img.title,
      width: img.image.width,
      height: img.image.height,
    }));
    
    console.log(JSON.stringify(jsonOutput, null, 2));
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    process.exit(1);
  }
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

searchGoogleImages(query, numResults);
