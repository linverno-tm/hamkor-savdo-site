/**
 * Admin yuklagan rasm: content.json da `aksiyalar/1726... ` kabi nom saqlanadi,
 * `tools/rasmlarni-tayyorlash.mjs` build oldidan `public/rasm/<nom>-480|960.webp`
 * yasaydi.
 */
export function uploadedImage(image: string) {
  if (!image) return null;
  return { src: `/rasm/${image}-960.webp`, srcSmall: `/rasm/${image}-480.webp` };
}

/** Toshkent (UTC+5, yozgi vaqt yo'q) bo'yicha bugungi sana, YYYY-MM-DD. */
export function tashkentToday(now = Date.now()) {
  return new Date(now + 5 * 3600 * 1000).toISOString().slice(0, 10);
}
