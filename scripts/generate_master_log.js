import fs from 'fs';
import path from 'path';

const baseDir = 'listings/publicados';
const outputFilePath = path.join(baseDir, 'MASTER_LOG.json');

function findJsonFiles(dir, fileList = []) {
  const files = fs.readdirSync(dir);
  files.forEach(file => {
    const filePath = path.join(dir, file);
    if (fs.statSync(filePath).isDirectory()) {
      findJsonFiles(filePath, fileList);
    } else if (path.extname(file) === '.json' && file !== 'MASTER_LOG.json' && file !== 'package.json' && file !== 'package-lock.json') {
      fileList.push(filePath);
    }
  });
  return fileList;
}

const allFiles = findJsonFiles(baseDir);
const masterLog = [];

allFiles.forEach(file => {
  try {
    const content = JSON.parse(fs.readFileSync(file, 'utf8'));
    // Handle both { data: { ... } } and direct { ... } structures
    const items = Array.isArray(content) ? content : [content];
    
    items.forEach(item => {
      const data = item.data || item.args || item;
      if (data.id || data.title) {
        masterLog.push({
          id: data.id || null,
          title: data.title || 'N/A',
          link: data.link || data.url || 'N/A',
          file: file
        });
      }
    });
  } catch (e) {
    console.error(`Error parsing ${file}: ${e.message}`);
  }
});

fs.writeFileSync(outputFilePath, JSON.stringify(masterLog, null, 2));
console.log(`✅ Master log generated with ${masterLog.length} entries at ${outputFilePath}`);
