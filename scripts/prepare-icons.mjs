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
