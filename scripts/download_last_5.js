const https = require('https');
const fs = require('fs');
const path = require('path');

const images = [
  { id: 6384, url: "https://images.pexels.com/photos/1032338/pexels-photo-1032338.jpeg" }, // Bungee
  { id: 6385, url: "https://images.pexels.com/photos/1320686/pexels-photo-1320686.jpeg" }, // Buceo
  { id: 6354, url: "https://images.pexels.com/photos/1436141/pexels-photo-1436141.jpeg" }, // Termales 1
  { id: 6356, url: "https://images.pexels.com/photos/1285625/pexels-photo-1285625.jpeg" }, // Termales 2
  { id: 6357, url: "https://images.pexels.com/photos/1436141/pexels-photo-1436141.jpeg" }  // Termales 3
];

const destDir = path.join(__dirname, '..', 'reparacion_imagenes');

async function run() {
    console.log(`🚀 Completando descarga de las últimas 5 imágenes...`);
    for (const img of images) {
        const dest = path.join(destDir, `${img.id}.jpg`);
        try {
            await new Promise((resolve, reject) => {
                https.get(img.url, { headers: { 'User-Agent': 'Mozilla/5.0' } }, (res) => {
                    const file = fs.createWriteStream(dest);
                    res.pipe(file);
                    file.on('finish', () => { file.close(); resolve(); });
                }).on('error', reject);
            });
            console.log(`✅ ID ${img.id} completado.`);
        } catch (e) {
            console.log(`❌ ID ${img.id} falló: ${e.message}`);
        }
    }
}
run();
