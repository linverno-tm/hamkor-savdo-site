/**
 * 3D vitrina modellari (components/sections/Vitrina3D.tsx).
 *
 * Fayllar `public/3d/<id>.glb` (siqilgan: WebP tekstura, kvantlangan geometriya)
 * va `public/3d/<id>.webp` (shaffof poster — 3D yuklanguncha va JavaScript'siz).
 * Manba: Sketchfab (CC BY — muallif va havola sahifada ko'rsatiladi, `credit`)
 * va Poly Haven (CC0). Yuklash: D:\Dasturlar\SketchfabMCP (egasining hisobi).
 *
 * `scale` — model haqiqiy o'lchamda bo'lsin (telefonda "xonangizda ko'ring"
 * uchun): ba'zi mualliflar millimetr/santimetrda yasagan. Tekshirilgan
 * o'lchamlar izohda.
 */
export type VitrinaKat = "texnika" | "mebel" | "tilla" | "skuter";

export interface VitrinaItem {
  id: string;
  kat: VitrinaKat;
  nom: { uz: string; ru: string };
  scale?: string;
  exposure?: number;
  /** Telefonda xonaga qo'yib ko'rish — tilla kabi mayda narsada ma'nosiz. */
  ar: boolean;
  credit: { title: string; author: string; authorUrl: string; url: string; license: "CC BY 4.0" | "CC0" };
}

const BY = "CC BY 4.0" as const;

export const VITRINA: VitrinaItem[] = [
  {
    id: "muzlatgich",
    kat: "texnika",
    nom: { uz: "Muzlatgich", ru: "Холодильник" },
    ar: true, // 0,96 × 1,82 × 0,62 m
    credit: { title: "French Door Refrigerator - Stainless", author: "Smokahontas", authorUrl: "https://sketchfab.com/Smokahontas", url: "https://sketchfab.com/3d-models/french-door-refrigerator-stainless-ebe62ed6dd9b446a9c9b7d7d6a8086e7", license: BY },
  },
  {
    id: "kir-mashina",
    kat: "texnika",
    nom: { uz: "Kir yuvish mashinasi", ru: "Стиральная машина" },
    scale: "0.001 0.001 0.001", // millimetrda yasalgan → 0,61 × 0,83 × 0,60 m
    exposure: 0.75,
    ar: true,
    credit: { title: "Washing Machine Samsung WF7602S8C", author: "Gnel-Mkrtchyan-1982", authorUrl: "https://sketchfab.com/Gnel-Mkrtchyan-1982", url: "https://sketchfab.com/3d-models/washing-machine-samsung-wf7602s8c-65ac032a09144e6298fb154d768c86a1", license: BY },
  },
  {
    id: "televizor",
    kat: "texnika",
    nom: { uz: "Televizor", ru: "Телевизор" },
    scale: "0.075 0.075 0.075", // → 0,98 m (43 dyuym)
    ar: true,
    credit: { title: "Led Tv", author: "Daniyal Malik", authorUrl: "https://sketchfab.com/daniyalmalik", url: "https://sketchfab.com/3d-models/led-tv-014e75456b4e465b9cecd1b136a0312b", license: BY },
  },
  {
    id: "konditsioner",
    kat: "texnika",
    nom: { uz: "Konditsioner", ru: "Кондиционер" },
    scale: "0.44 0.44 0.44", // → 0,90 m
    ar: true,
    credit: { title: "Air condition Daikin", author: "maxsbond.work", authorUrl: "https://sketchfab.com/maxsbond.work", url: "https://sketchfab.com/3d-models/air-condition-daikin-26b0f7c8472741f4b85b889cf3b1d870", license: BY },
  },
  {
    id: "mikroto-lqin",
    kat: "texnika",
    nom: { uz: "Mikroto'lqinli pech", ru: "Микроволновая печь" },
    scale: "0.01 0.01 0.01", // santimetrda → 0,55 m
    ar: true,
    credit: { title: "Microwave Oven", author: "MustafaYerebasmaz", authorUrl: "https://sketchfab.com/Mustafa97", url: "https://sketchfab.com/3d-models/microwave-oven-3421e492257f40959859104f46128304", license: BY },
  },
  {
    id: "divan",
    kat: "mebel",
    nom: { uz: "Divan", ru: "Диван" },
    ar: true, // 2,73 m
    credit: { title: "Sofa 03", author: "Fran Calvente", authorUrl: "https://polyhaven.com/a/sofa_03", url: "https://polyhaven.com/a/sofa_03", license: "CC0" },
  },
  {
    id: "kreslo",
    kat: "mebel",
    nom: { uz: "Kreslo", ru: "Кресло" },
    ar: true,
    credit: { title: "Modern Arm Chair 01", author: "Vibrant Nordic", authorUrl: "https://polyhaven.com/a/modern_arm_chair_01", url: "https://polyhaven.com/a/modern_arm_chair_01", license: "CC0" },
  },
  {
    id: "uzuk",
    kat: "tilla",
    nom: { uz: "Uzuk", ru: "Кольцо" },
    ar: false,
    credit: { title: "The Ring: 1 carat", author: "Evan", authorUrl: "https://sketchfab.com/Evanz", url: "https://sketchfab.com/3d-models/the-ring-1-carat-378f6df2c4f444399b83db2037258470", license: BY },
  },
  {
    id: "zanjir",
    kat: "tilla",
    nom: { uz: "Zanjir", ru: "Цепочка" },
    ar: false,
    credit: { title: "Gold necklace / chain", author: "Mckai", authorUrl: "https://sketchfab.com/Mckai", url: "https://sketchfab.com/3d-models/gold-necklace-chain-8bb2ebea4fbd4a3a9b21cba9f5d5fdc8", license: BY },
  },
  {
    id: "skuter",
    kat: "skuter",
    nom: { uz: "Skuter", ru: "Скутер" },
    ar: true, // 1,83 m
    credit: { title: "Moto Vespa", author: "mohamed ouartassi", authorUrl: "https://sketchfab.com/mohamedouartassi", url: "https://sketchfab.com/3d-models/moto-vespa-f1c7cacdbc954b48a4062a4734617afb", license: BY },
  },
];

export const VITRINA_KAT: { id: VitrinaKat; uz: string; ru: string }[] = [
  { id: "texnika", uz: "Texnika", ru: "Техника" },
  { id: "mebel", uz: "Mebel", ru: "Мебель" },
  { id: "tilla", uz: "Tilla", ru: "Золото" },
  { id: "skuter", uz: "Skuter", ru: "Скутеры" },
];

/** Shu skript versiyasi `public/vendor/` da (Apache-2.0, @google/model-viewer). */
export const MODEL_VIEWER_SRC = "/vendor/model-viewer-4.4.0.min.js";
