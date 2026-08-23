/**
 * "Nega HAMKOR SAVDO?" — verified advantages only.
 * Every entry traces to the official Telegram channel bio or the Instagram bio.
 */

export interface Advantage {
  id: string;
  title: string;
  detail: string;
}

export const advantages: Advantage[] = [
  {
    id: "installment",
    title: "24 oygacha muddatli to'lov",
    detail:
      "Mahsulotni bugun olib keting, to'lovni o'zingizga qulay muddatga bo'lib to'lang — 24 oygacha.",
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
      "Xaridingizni manzilingizga bepul yetkazamiz — Andijon viloyati bo'ylab.",
  },
  {
    id: "installation",
    title: "Bepul o'rnatish",
    detail:
      "Texnikani ustalarimiz bepul o'rnatib beradi. Xariddan keyin ham yolg'iz qolmaysiz.",
  },
  {
    id: "assortment",
    title: "7000 dan ortiq mahsulot",
    detail:
      "Tilla, texnika va mebel — uch yo'nalish, minglab mahsulot. Boshqa do'kon qidirish shart emas.",
  },
  {
    id: "branches",
    title: "4 ta filial",
    detail:
      "Shahrixon, Asaka va Andijonda — sizga eng yaqin filialga kirib, mahsulotni jonli ko'rasiz.",
  },
];
