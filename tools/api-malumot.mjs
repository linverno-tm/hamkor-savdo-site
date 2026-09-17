/**
 * data/content.json -> public/api/sayt.json (npm "prebuild").
 *
 * Serverdagi PHP (lead.php, admin panel) saytda HOZIR nima turganini shu
 * fayldan biladi: filial nomlari, telefonlar. Veb orqali ochilmaydi
 * (.htaccess), faqat serverning o'zi o'qiydi. Git'ga tushmaydi.
 */
import { readFileSync, writeFileSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const content = JSON.parse(readFileSync(join(ROOT, "data", "content.json"), "utf8"));
writeFileSync(join(ROOT, "public", "api", "sayt.json"), JSON.stringify(content));
console.log(`api/sayt.json: ${content.branches.length} ta filial`);
