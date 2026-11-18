const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const RESOURCES = path.join(__dirname, '..', 'resources');

const tasks = [
  { src: 'icon.svg', dest: 'icon.png', size: 1024 },
  { src: 'icon-foreground.svg', dest: 'icon-foreground.png', size: 1024 },
  { src: 'icon-background.svg', dest: 'icon-background.png', size: 1024 },
  { src: 'splash.svg', dest: 'splash.png', size: 2732 },
];

(async () => {
  for (const t of tasks) {
    const src = path.join(RESOURCES, t.src);
    const dest = path.join(RESOURCES, t.dest);

    if (!fs.existsSync(src)) {
      console.log('Skip ' + t.src + ' (not found)');
      continue;
    }

    await sharp(src, { density: 300 })
      .resize(t.size, t.size, {
        fit: 'contain',
        background: { r: 0, g: 0, b: 0, alpha: 0 },
      })
      .png({ compressionLevel: 9 })
      .toFile(dest);

    const stats = fs.statSync(dest);
    console.log('OK ' + t.dest + ' (' + Math.round(stats.size / 1024) + ' KB)');
  }
  console.log('Selesai! Cek folder resources/');
})().catch((err) => {
  console.error('Error:', err.message);
  process.exit(1);
});
