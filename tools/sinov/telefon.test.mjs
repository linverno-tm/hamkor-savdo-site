/**
 * Telefon formatlovchisi (lib/telefon.ts) sinovi: odam raqamni harfma-harf
 * yozgandek har belgidan keyin formatlanadi.  npm test
 */
import { test } from "node:test";
import assert from "node:assert/strict";
import { telefonFormat } from "../../lib/telefon.ts";

const yoz = (s) => {
  let v = "";
  for (const ch of s) v = telefonFormat(v + ch);
  return v;
};

test("odatiy yozilishlar +998 ko'rinishiga keladi", () => {
  assert.equal(yoz("901234567"), "+998 90 123 45 67");
  assert.equal(yoz("998901234567"), "+998 90 123 45 67");
  assert.equal(yoz("+998901234567"), "+998 90 123 45 67");
  assert.equal(yoz("+998 90 123 45 67"), "+998 90 123 45 67");
  assert.equal(yoz("881234567"), "+998 88 123 45 67");
});

test("boshida 0 bilan yozilgan raqam buzilmaydi (22.10 xatosi)", () => {
  assert.equal(yoz("0901234567"), "+998 90 123 45 67");
  assert.equal(yoz("090 123 45 67"), "+998 90 123 45 67");
});

test("xorijiy raqamga tegilmaydi", () => {
  assert.equal(yoz("+79161234567"), "+79161234567");
});

test("bo'sh va chala raqam", () => {
  assert.equal(yoz(""), "");
  assert.equal(yoz("+"), "+");
  assert.equal(yoz("90123"), "90 123");
});
