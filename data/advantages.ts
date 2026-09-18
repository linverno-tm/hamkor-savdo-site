/**
 * "Nega HAMKOR SAVDO?" — verified advantages only.
 * Every entry traces to the official Telegram channel bio or the Instagram bio.
 */

/*
 * Muddat bitta joyda — data/site.ts dagi `installmentMonthsMax`.
 * Sabab: Asaka filialidagi bannerda "18 oygacha" yozuvi bor, saytda esa
 * hamma joyda 24 turibdi. Raqam o'zgarsa, bitta qatorni tuzatish yetarli
 * bo'lsin — matnlar bo'ylab qidirib yurilmasin.
 */
import { site } from "./site";
import { cityList } from "./branches";


export interface Advantage {
  id: string;
  title: string;
  detail: string;
}

export const advantages: Advantage[] = [
  {
    id: "installment",
    title: `${site.facts.installmentMonthsMax} oygacha muddatli to'lov`,
    detail:
      `Mahsulotni bugun olib keting, to'lovni o'zingizga qulay muddatga bo'lib to'lang — ${site.facts.installmentMonthsMax} oygacha.`,
  },
  {
    id: "documents",
    title: "Pasport va plastik kifoya",
    detail:
      "Rasmiylashtirish uchun ortiqcha hujjat, kafil yoki ma'lumotnoma kerak emas. Pasport va plastik kartaning o'zi yetarli.",
  },
  {
    id: "delivery",
    title: "Bepul yetkazib berish",
    detail:
      `${site.freeDeliveryArea}da yetkazish bepul. ${site.deliveryArea} ham yetkazib beramiz — boshqa viloyatlar uchun shartlarni qo'ng'iroqda aytamiz.`,
  },
  {
    id: "installation",
    title: "Bepul o'rnatish",
    detail:
      `Texnikani ustalarimiz ${site.freeDeliveryArea}da bepul o'rnatib beradi. Xariddan keyin ham yolg'iz qolmaysiz.`,
  },
  {
    id: "assortment",
    title: `${site.facts.productCount.replace("+", " dan ortiq")} mahsulot`,
    detail:
      "Tilla, texnika va mebel — uch yo'nalish, minglab mahsulot. Boshqa do'kon qidirish shart emas.",
  },
  {
    id: "branches",
    title: `${site.facts.branchCount} ta filial`,
    detail: `${cityList({ counts: false })}da — sizga eng yaqin filialga kirib, mahsulotni jonli ko'rasiz.`,
  },
];
