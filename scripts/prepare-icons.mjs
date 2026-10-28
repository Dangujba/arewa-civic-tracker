import { cp, mkdir, readFile, writeFile, copyFile } from 'node:fs/promises';
import { join } from 'node:path';

const root = process.cwd();
const modules = join(root, 'node_modules');
const output = join(root, 'public/vendor');
const faSource = join(modules, '@fortawesome/fontawesome-free');
const lucideSource = join(modules, 'lucide-static/icons');
const faOutput = join(output, 'fontawesome');
const iconOutput = join(output, 'lucide');
await mkdir(faOutput, { recursive: true });
await mkdir(iconOutput, { recursive: true });
await cp(join(faSource, 'webfonts'), join(faOutput, 'webfonts'), { recursive: true });
const css = await readFile(join(faSource, 'css/all.min.css'), 'utf8');
await writeFile(join(faOutput, 'all.min.css'), css.replaceAll('../webfonts/', './webfonts/'));
const icons = [
  'map-pin', 'map-pinned', 'clipboard-check', 'file-check-2', 'building-2',
  'route', 'shield-check', 'users-round', 'chart-column', 'file-chart-column',
  'check-check', 'chart-no-axes-combined', 'camera', 'refresh-cw', 'smartphone',
  'list-check', 'file-search', 'database', 'radio-tower', 'target', 'eye',
  'notebook-pen', 'book-open', 'network', 'check-circle',
];
for (const name of icons) {
  await copyFile(join(lucideSource, name + '.svg'), join(iconOutput, name + '.svg'));
}
console.log('Prepared local Font Awesome and Lucide assets (' + icons.length + ' Lucide icons).');

// Leaflet mapping libraries are bundled locally, not loaded from a third-party CDN.
const mapOutput = join(output, 'maps');
await mkdir(mapOutput, { recursive: true });
for (const [name, src] of Object.entries({
  'leaflet.js': join(modules, 'leaflet/dist/leaflet.js'),
  'leaflet.css': join(modules, 'leaflet/dist/leaflet.css'),
  'markercluster.js': join(modules, 'leaflet.markercluster/dist/leaflet.markercluster.js'),
  'markercluster.css': join(modules, 'leaflet.markercluster/dist/MarkerCluster.css'),
  'markercluster-default.css': join(modules, 'leaflet.markercluster/dist/MarkerCluster.Default.css'),
  'heat.js': join(modules, 'leaflet.heat/dist/leaflet-heat.js'),
})) {
  await copyFile(src, join(mapOutput, name));
}
await cp(join(modules, 'leaflet/dist/images'), join(mapOutput, 'images'), { recursive: true });
console.log('Prepared local Leaflet, clustering and heatmap assets.');

const chartOutput = join(output, 'charts');
await mkdir(chartOutput, { recursive: true });
await copyFile(join(modules, 'chart.js/dist/chart.umd.js'), join(chartOutput, 'chart.umd.js'));
console.log('Prepared local Chart.js reporting asset.');

const uiOutput = join(output, 'ui');
await mkdir(uiOutput, { recursive: true });
for (const [name, path] of Object.entries({
 'tom-select.js': join(modules,'tom-select/dist/js/tom-select.complete.min.js'),
 'tom-select.css': join(modules,'tom-select/dist/css/tom-select.default.min.css'),
 'sweetalert2.js': join(modules,'sweetalert2/dist/sweetalert2.all.min.js'),
 'sweetalert2.css': join(modules,'sweetalert2/dist/sweetalert2.min.css')
})) await copyFile(path, join(uiOutput,name));
console.log('Prepared local lightweight select and dialog assets.');


// Rebuild the multiresolution ICO from the committed raster assets.
const faviconSizes = [16, 32, 48, 64];
const faviconPngs = await Promise.all(faviconSizes.map(size =>
  readFile(join(root, 'public/icons', 'favicon-' + size + '.png'))
));
const headerSize = 6 + 16 * faviconPngs.length;
const favicon = Buffer.alloc(headerSize + faviconPngs.reduce((size, png) => size + png.length, 0));
favicon.writeUInt16LE(0, 0);
favicon.writeUInt16LE(1, 2);
favicon.writeUInt16LE(faviconPngs.length, 4);
let faviconOffset = headerSize;
for (let index = 0; index < faviconPngs.length; index++) {
  const size = faviconSizes[index], png = faviconPngs[index];
  const offset = 6 + index * 16;
  favicon.writeUInt8(size, offset);
  favicon.writeUInt8(size, offset + 1);
  favicon.writeUInt8(0, offset + 2);
  favicon.writeUInt8(0, offset + 3);
  favicon.writeUInt16LE(1, offset + 4);
  favicon.writeUInt16LE(32, offset + 6);
  favicon.writeUInt32LE(png.length, offset + 8);
  favicon.writeUInt32LE(faviconOffset, offset + 12);
  png.copy(favicon, faviconOffset);
  faviconOffset += png.length;
}
await writeFile(join(root, 'public/favicon.ico'), favicon);
console.log('Generated favicon.ico from approved hand-drawn icon assets.');
