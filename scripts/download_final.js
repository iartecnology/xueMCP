const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');

// Mapeo curado de IDs de listados con URLs directas que funcionan
const images = [
  // Trekking / Experiencias
  { id: 6389, url: "https://images.pexels.com/photos/14384041/pexels-photo-14384041.jpeg" }, // Sumapaz / Paisaje
  { id: 6390, url: "https://images.pexels.com/photos/7368311/pexels-photo-7368311.jpeg" }, // Chicamocha
  { id: 6391, url: "https://images.pexels.com/photos/460621/pexels-photo-460621.jpeg" }, // Puracé / Bosque
  { id: 6392, url: "https://images.pexels.com/photos/2356045/pexels-photo-2356045.jpeg" }, // Lindosa
  { id: 6393, url: "https://images.pexels.com/photos/16458974/pexels-photo-16458974.jpeg" }, // Cocora / Salento
  
  // Aventura
  { id: 6384, url: "https://images.pexels.com/photos/2032338/pexels-photo-2032338.jpeg" }, // Bungee
  { id: 6385, url: "https://images.pexels.com/photos/1769641/pexels-photo-1769641.jpeg" }, // Buceo
  { id: 6386, url: "https://images.pexels.com/photos/16458977/pexels-photo-16458977.jpeg" }, // Canyoning / San Gil
  { id: 6387, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Kitesurf / Playa
  { id: 6388, url: "https://images.pexels.com/photos/16458977/pexels-photo-16458977.jpeg" }, // Torrentismo
  
  // Sabores Batch 29
  { id: 6366, url: "https://images.pexels.com/photos/10006534/pexels-photo-10006534.jpeg" }, // Pirarucu / Amazonas
  { id: 6367, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Pescado Moqueado
  { id: 6368, url: "https://images.pexels.com/photos/11299734/pexels-photo-11299734.jpeg" }, // Casabe / Arepa base
  { id: 6369, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Copoazu / Frutas
  { id: 6370, url: "https://images.pexels.com/photos/15243353/pexels-photo-15243353.jpeg" }, // Carne Perra / Llanera
  { id: 6371, url: "https://images.pexels.com/photos/11299734/pexels-photo-11299734.jpeg" }, // Hayaca
  
  // Sabores Batch 30
  { id: 6372, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Hormigas / Exotico
  { id: 6373, url: "https://images.pexels.com/photos/15243353/pexels-photo-15243353.jpeg" }, // Cuy
  { id: 6374, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Helado Paila
  { id: 6375, url: "https://images.pexels.com/photos/11299734/pexels-photo-11299734.jpeg" }, // Arepa Santandereana
  { id: 6376, url: "https://images.pexels.com/photos/15243353/pexels-photo-15243353.jpeg" }, // Cabrito
  { id: 6377, url: "https://images.pexels.com/photos/15243353/pexels-photo-15243353.jpeg" }, // Pepitoria
  
  // Sabores Batch 31
  { id: 6378, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Salpicon
  { id: 6379, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Cholado
  { id: 6380, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Champus
  { id: 6381, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Lulada
  { id: 6382, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Arroz Leche
  { id: 6383, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Merengon
  
  // Sabores Principales
  { id: 6361, url: "https://images.pexels.com/photos/15243353/pexels-photo-15243353.jpeg" }, // Bandeja Paisa
  { id: 6362, url: "https://images.pexels.com/photos/15243353/pexels-photo-15243353.jpeg" }, // Mondongo
  { id: 6363, url: "https://images.pexels.com/photos/15243353/pexels-photo-15243353.jpeg" }, // Fritanga
  { id: 6364, url: "https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg" }, // Cazuela
  { id: 6365, url: "https://images.pexels.com/photos/11299734/pexels-photo-11299734.jpeg" }, // Arroz Pollo
  
  // Rios y Termales
  { id: 6358, url: "https://images.pexels.com/photos/14384051/pexels-photo-14384051.jpeg" }, // Magdalena / Mompox
  { id: 6359, url: "https://images.pexels.com/photos/14384049/pexels-photo-14384049.jpeg" }, // Cauca / Popayan
  { id: 6360, url: "https://images.pexels.com/photos/16458974/pexels-photo-16458974.jpeg" }, // Guatape
  { id: 6354, url: "https://images.pexels.com/photos/13614793/pexels-photo-13614793.jpeg" }, // Termales San Vicente
  { id: 6355, url: "https://images.pexels.com/photos/14384044/pexels-photo-14384044.jpeg" }, // Termales Ruiz
  { id: 6356, url: "https://images.pexels.com/photos/13614793/pexels-photo-13614793.jpeg" }, // Termales Paipa
  { id: 6357, url: "https://images.pexels.com/photos/13614793/pexels-photo-13614793.jpeg" }, // Termales Coconuco
  
  // Otros
  { id: 6397, url: "https://images.pexels.com/photos/2356045/pexels-photo-2356045.jpeg" }, // Teotihuacan
  { id: 6353, url: "https://images.pexels.com/photos/460621/pexels-photo-460621.jpeg" } // Los Katios / Paisaje
];

const destDir = path.join(__dirname, '..', 'reparacion_imagenes');

if (!fs.existsSync(destDir)) {
    fs.mkdirSync(destDir, { recursive: true });
}

const download = (url, dest) => {
    return new Promise((resolve, reject) => {
        const client = url.startsWith('https') ? https : http;
        const options = {
            headers: {
                'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept': 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8'
            }
        };
        client.get(url, options, (res) => {
            if (res.statusCode === 301 || res.statusCode === 302) {
                return download(res.headers.location, dest).then(resolve).catch(reject);
            }
            if (res.statusCode !== 200) {
                return reject(new Error(`Status: ${res.statusCode}`));
            }
            const file = fs.createWriteStream(dest);
            res.pipe(file);
            file.on('finish', () => {
                file.close();
                resolve();
            });
            file.on('error', (err) => {
                fs.unlink(dest, () => {});
                reject(err);
            });
        }).on('error', (err) => {
            reject(err);
        });
    });
};

async function run() {
    console.log(`🚀 Iniciando descarga de ${images.length} imágenes...`);
    let success = 0;
    
    for (let i = 0; i < images.length; i++) {
        const img = images[i];
        const filename = `${img.id}.jpg`;
        const dest = path.join(destDir, filename);
        
        process.stdout.write(`⏬ [${i + 1}/${images.length}] ID ${img.id}... `);
        
        try {
            await download(img.url, dest);
            process.stdout.write(`✅ OK\n`);
            success++;
        } catch (err) {
            process.stdout.write(`❌ Error: ${err.message}\n`);
        }
    }
    console.log(`\n✨ Finalizado. Éxitos: ${success}/${images.length}`);
    console.log(`📂 Las imágenes están en: ${destDir}`);
}

run();
