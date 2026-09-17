/**
 * Admin paneldan yuklangan asl rasmlarni saytga tayyorlaydi (npm "prebuild").
 *
 *   rasmlar/<guruh>/<nom>.<jpg|jpeg|png|webp>
 *     -> public/rasm/<guruh>/<nom>-480.webp va -960.webp  (4:3, markazdan kesilgan)
 *
 * Guruhlar: filiallar/<slug>, aksiyalar, katalog.
 * public/rasm/ git'ga tushmaydi — har build'da shu yerdan qayta yasaladi.
 * O'zgarmagan rasm qayta ishlanmaydi (fayl vaqti bo'yicha).
 */
import { readdirSync, statSync, mkdirSync, existsSync, rmSync, writeFileSync } from "node:fs";
import { join, dirname, relative, extname, basename } from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const SRC = join(ROOT, "rasmlar");
const OUT = join(ROOT, "public", "rasm");
const EXT = new Set([".jpg", ".jpeg", ".png", ".webp"]);
const SIZES = [480, 960];
const RATIO = 4 / 3;

function walk(dir) {
  if (!existsSync(dir)) return [];
  const out = [];
  for (const name of readdirSync(dir)) {
    const full = join(dir, name);
    if (statSync(full).isDirectory()) out.push(...walk(full));
    else if (EXT.has(extname(name).toLowerCase())) out.push(full);
  }
  return out;
}

const sources = walk(SRC);
const expected = new Set();
const manifest = {};
let made = 0;

for (const file of sources) {
  const rel = relative(SRC, file).split("\\").join("/");
  const key = rel.slice(0, -extname(rel).length); // guruh/nom
  manifest[key] = `rasmlar/${rel}`;
  const srcTime = statSync(file).mtimeMs;

  for (const size of SIZES) {
    const target = join(OUT, `${key}-${size}.webp`);
    expected.add(target);
    if (existsSync(target) && statSync(target).mtimeMs >= srcTime) continue;
    mkdirSync(dirname(target), { recursive: true });
    await sharp(file)
      .rotate()
      .resize(size, Math.round(size / RATIO), { fit: "cover", position: "centre" })
      .webp({ quality: 82 })
      .toFile(target);
    made++;
  }
}

// Asl rasmi o'chirilgan eski natijalarni tozalash.
for (const file of walk(OUT)) {
  if (file.endsWith(".webp") && !expected.has(file)) rmSync(file);
}

mkdirSync(OUT, { recursive: true });
writeFileSync(join(OUT, "manifest.json"), JSON.stringify(manifest, null, 2));
console.log(`rasmlar: ${sources.length} ta asl, ${made} ta yangi webp -> public/rasm/`);
