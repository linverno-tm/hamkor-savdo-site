/**
 * The installment journey. The four steps describe the generic in-store flow;
 * the two hard facts (24 months, passport + card) are verified from the
 * Instagram bio.
 */

export interface InstallmentStep {
  index: string;
  title: string;
  detail: string;
}

export const installmentSteps: InstallmentStep[] = [
  {
    index: "01",
    title: "Mahsulotni tanlang",
    detail: "Do'konga keling — tilla, texnika yoki mebel bo'limidan keraklisini tanlang.",
  },
  {
    index: "02",
    title: "Rasmiylashtiring",
    detail: "Pasport va plastik kartangiz bilan joyida rasmiylashtiramiz.",
  },
  {
    index: "03",
    title: "Mahsulotni oling",
    detail: "Xaridingizni shu kuniyoq olib ketasiz yoki yetkazib beramiz.",
  },
  {
    index: "04",
    title: "Qulay muddatda to'lang",
    detail: "To'lovni 24 oygacha bo'lib to'laysiz.",
  },
];
