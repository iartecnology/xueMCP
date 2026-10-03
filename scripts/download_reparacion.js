const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');

const images = [
  { id: 6389, url: "https://upload.wikimedia.org/wikipedia/commons/e/ea/P%C3%A1ramo_de_Sumapaz_01.jpg" },
  { id: 6390, url: "https://upload.wikimedia.org/wikipedia/commons/3/36/Ca%C3%B1on_del_Chicamocha.jpg" },
  { id: 6391, url: "https://upload.wikimedia.org/wikipedia/commons/1/15/Purac%C3%A9_Volcano.jpg" },
  { id: 6392, url: "https://upload.wikimedia.org/wikipedia/commons/d/d4/Pictogramas_Guaviare.jpg" },
  { id: 6393, url: "https://upload.wikimedia.org/wikipedia/commons/3/30/Valle_de_Cocora.jpg" },
  { id: 6384, url: "https://images.pexels.com/photos/2032338/pexels-photo-2032338.jpeg" },
  { id: 6385, url: "https://images.pexels.com/photos/1769641/pexels-photo-1769641.jpeg" },
  { id: 6386, url: "https://images.pexels.com/photos/2356045/pexels-photo-2356045.jpeg" },
  { id: 6387, url: "https://images.pexels.com/photos/2356045/pexels-photo-2356045.jpeg" }, // Placeholder similar
  { id: 6388, url: "https://images.pexels.com/photos/2356045/pexels-photo-2356045.jpeg" }, // Placeholder similar
  { id: 6366, url: "https://upload.wikimedia.org/wikipedia/commons/0/01/Arapaima_gigas_01.jpg" },
  { id: 6367, url: "https://upload.wikimedia.org/wikipedia/commons/d/d2/Pescado_moqueado.jpg" },
  { id: 6368, url: "https://upload.wikimedia.org/wikipedia/commons/4/4d/Casabe.jpg" },
  { id: 6369, url: "https://upload.wikimedia.org/wikipedia/commons/6/6c/Copoaz%C3%BA.jpg" },
  { id: 6370, url: "https://upload.wikimedia.org/wikipedia/commons/b/b5/Carne_a_la_llanera.jpg" },
  { id: 6371, url: "https://upload.wikimedia.org/wikipedia/commons/6/6f/Hallaca_colombiana.jpg" },
  { id: 6372, url: "https://upload.wikimedia.org/wikipedia/commons/1/15/Hormigas_culonas.jpg" },
  { id: 6373, url: "https://upload.wikimedia.org/wikipedia/commons/4/4e/Cuy_asado.jpg" },
  { id: 6374, url: "https://upload.wikimedia.org/wikipedia/commons/d/df/Helado_de_paila.jpg" },
  { id: 6375, url: "https://upload.wikimedia.org/wikipedia/commons/3/3d/Arepa_santandereana.jpg" },
  { id: 6376, url: "https://upload.wikimedia.org/wikipedia/commons/7/77/Cabrito_asado.jpg" },
  { id: 6377, url: "https://upload.wikimedia.org/wikipedia/commons/c/c5/Pepitoria_santandereana.jpg" },
  { id: 6378, url: "https://upload.wikimedia.org/wikipedia/commons/d/d0/Salpicon_de_frutas.jpg" },
  { id: 6379, url: "https://upload.wikimedia.org/wikipedia/commons/2/22/Cholado_en_Jamund%C3%AD.jpg" },
  { id: 6380, url: "https://upload.wikimedia.org/wikipedia/commons/3/3a/Champ%C3%BAs_valluno.jpg" },
  { id: 6381, url: "https://upload.wikimedia.org/wikipedia/commons/0/0c/Lulada.jpg" },
  { id: 6382, url: "https://upload.wikimedia.org/wikipedia/commons/b/b2/Arroz_con_leche_colombiano.jpg" },
  { id: 6383, url: "https://upload.wikimedia.org/wikipedia/commons/8/87/Mereng%C3%B3n_de_guan%C3%A1bana.jpg" },
  { id: 6361, url: "https://upload.wikimedia.org/wikipedia/commons/e/e9/Bandeja_paisa%2C_plato_Colombiano.jpg" },
  { id: 6362, url: "https://upload.wikimedia.org/wikipedia/commons/8/87/Mondongo_colombiano.jpg" },
  { id: 6363, url: "https://upload.wikimedia.org/wikipedia/commons/d/d4/Fritanga_Bogotana.jpg" },
  { id: 6364, url: "https://upload.wikimedia.org/wikipedia/commons/d/df/Cazuela_de_mariscos.jpg" },
  { id: 6365, url: "https://upload.wikimedia.org/wikipedia/commons/b/be/Arroz_con_pollo_01.jpg" },
  { id: 6397, url: "https://images.pexels.com/photos/2356045/pexels-photo-2356045.jpeg" },
  { id: 6358, url: "https://upload.wikimedia.org/wikipedia/commons/c/cc/Rio_Magdalena_en_Honda.jpg" },
  { id: 6359, url: "https://upload.wikimedia.org/wikipedia/commons/3/35/Rio_Cauca_La_Pintada.jpg" },
  { id: 6360, url: "https://upload.wikimedia.org/wikipedia/commons/2/23/Embalse_Guatap%C3%A9.jpg" },
  { id: 6354, url: "https://upload.wikimedia.org/wikipedia/commons/e/e0/Termales_San_Vicente.jpg" },
  { id: 6355, url: "https://upload.wikimedia.org/wikipedia/commons/7/77/Nevado_del_Ruiz_desde_Termales.jpg" },
  { id: 6356, url: "https://upload.wikimedia.org/wikipedia/commons/6/6b/Pantano_de_Vargas_Paipa.jpg" },
  { id: 6357, url: "https://upload.wikimedia.org/wikipedia/commons/3/3c/Termales_Coconuco.jpg" }
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
                'Referer': 'https://www.google.com/'
            }
        };
        client.get(url, options, (res) => {
            if (res.statusCode === 301 || res.statusCode === 302) {
                return download(res.headers.location, dest).then(resolve).catch(reject);
            }
            if (res.statusCode !== 200) {
                return reject(new Error(`Status Code: ${res.statusCode}`));
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
    let failed = 0;
    
    for (const img of images) {
        const filename = `${img.id}.jpg`;
        const dest = path.join(destDir, filename);
        console.log(`⏬ [${success + failed + 1}/${images.length}] Descargando ${img.id}...`);
        try {
            await download(img.url, dest);
            console.log(`✅ Guardado: ${filename}`);
            success++;
        } catch (err) {
            console.error(`❌ Error con ID ${img.id}: ${err.message}`);
            failed++;
        }
    }
    console.log(`\n✨ Finalizado. Éxitos: ${success}, Fallidos: ${failed}`);
    console.log(`📂 Ubicación: ${destDir}`);
}

run();
