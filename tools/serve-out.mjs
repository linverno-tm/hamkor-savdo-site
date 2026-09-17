import { createServer } from "node:http";
import { readFile, stat } from "node:fs/promises";
import { join, extname } from "node:path";

const ROOT = join(import.meta.dirname, "..", "out");
const TYPES = {
  ".html": "text/html",
  ".css": "text/css",
  ".js": "text/javascript",
  ".webp": "image/webp",
  ".svg": "image/svg+xml",
  ".webmanifest": "application/manifest+json",
  ".png": "image/png",
  ".ico": "image/x-icon",
  ".xml": "application/xml",
  ".txt": "text/plain",
};

createServer(async (req, res) => {
  let p = decodeURIComponent(req.url.split("?")[0]);
  let full = join(ROOT, p);
  try {
    let st = await stat(full);
    if (st.isDirectory()) full = join(full, "index.html");
    const data = await readFile(full);
    res.writeHead(200, { "Content-Type": TYPES[extname(full)] || "application/octet-stream" });
    res.end(data);
  } catch {
    res.writeHead(404);
    res.end("not found: " + p);
  }
}).listen(4173, () => console.log("out/ shu yerda: http://localhost:4173"));
