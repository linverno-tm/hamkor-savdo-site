import { defineConfig, globalIgnores } from "eslint/config";
import nextVitals from "eslint-config-next/core-web-vitals";
import nextTypeScript from "eslint-config-next/typescript";

/**
 * ESLint sozlamasi.
 *
 * `package.json` da "lint" skripti bor edi, lekin sozlama fayli yo'q edi —
 * `npm run lint` xato bilan to'xtar edi, ya'ni loyihada tekshiruv umuman
 * ishlamayotgan edi.
 *
 * `eslint-config-next` (16-versiya) yangi "flat" formatni o'zi beradi,
 * shuning uchun FlatCompat kerak emas.
 */
export default defineConfig([
  ...nextVitals,
  ...nextTypeScript,
  globalIgnores([
    // eslint-config-next ning o'z ro'yxati (uni qayta yozganimiz uchun
    // shu yerda takrorlanadi) + bizning statik eksport va PHP qismimiz.
    ".next/**",
    "out/**",
    "build/**",
    "next-env.d.ts",
    "public/**",
  ]),
  {
    rules: {
      /* Sayt statik eksport (`output: "export"`) bilan yig'iladi, ya'ni
         next/image ishlamaydi va rasmlar `tools/rasmlarni-tayyorlash.mjs`
         orqali oldindan webp ga aylantirilgan. Shuning uchun <img> ataylab
         ishlatilgan — ogohlantirish har safar chiqib turmasin. */
      "@next/next/no-img-element": "off",
    },
  },
]);
