import { copyFileSync, existsSync, mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

// Ship the official Quill 2 distribution locally for low-bandwidth sites.
// No custom WYSIWYG implementation, icons or remote CDN dependency.
const base = resolve('node_modules/quill/dist');
const output = resolve('public/vendor/quill');
if (!existsSync(resolve(base, 'quill.js'))) {
  throw new Error('Run npm ci before preparing Quill assets');
}
mkdirSync(output, { recursive: true });
for (const file of ['quill.js', 'quill.snow.css']) {
  copyFileSync(resolve(base, file), resolve(output, file));
}
console.log('Copied Quill 2 Snow editor to public/vendor/quill');
