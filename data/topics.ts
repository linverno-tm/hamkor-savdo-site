import { site } from "./site";
import { branches } from "./branches";

/**
 * Qidiruvga mo'ljallangan yo'nalish sahifalari: /texnika/, /mebel/, /tilla/,
 * /muddatli-tolov/ va ularning ruscha nusxalari /ru/... .
 *
 * Nega alohida sahifa: bosh sahifa to'rt narsani birdan sotadi va shuning
 * uchun "Shahrixon maishiy texnika rassrochka" kabi aniq so'rovda hech
 * biriga to'liq javob bo'lmaydi. Har bir sahifa bitta savolga javob beradi
 * va sarlavhasida odam aynan yozadigan so'zlar turadi.
 *
 * FAKTLAR: faqat saytda allaqachon tasdiqlanganlari — muddat (sozlamadan),
 * pasport + plastik karta, bepul yetkazish/o'rnatish faqat
 * `freeDeliveryArea` da, filiallar ro'yxati, "do'konda yo'q mahsulot" xizmati.
 * Brend, narx, kafolat, foiz — egasi bermagan, shuning uchun YO'Q.
 */

export type TopicId = "texnika" | "skuter" | "mebel" | "tilla" | "muddatli-tolov";
export type Lang = "uz" | "ru";

export interface TopicText {
  /** <title> — odam qidiradigan so'zlar birinchi. */
  title: string;
  description: string;
  kicker: string;
  h1: string;
  lead: string;
  /** "Nima uchun bizda" — 4 ta qisqa band. */
  points: { title: string; text: string }[];
  /** Asosiy matn — Google sahifa nima haqida ekanini shundan tushunadi. */
  body: string[];
  faq: { q: string; a: string }[];
  cta: string;
}

export interface Topic {
  id: TopicId;
  /** O'zbekcha manzil: /texnika/ */
  path: string;
  /** Ruscha manzil: /ru/tehnika/ */
  ruPath: string;
  /** Sahifa rasmi (bosh sahifadagi yo'nalish suratlaridan). */
  photo?: { src: string; alt: { uz: string; ru: string } };
  uz: TopicText;
  ru: TopicText;
}

const M = site.facts.installmentMonthsMax;
const N = site.facts.productCount;
const FREE = site.freeDeliveryArea; // "Andijon viloyati"

/** "Shahrixon, Asaka va Andijon" — filiallar turgan shaharlar. */
function cities(lang: Lang) {
  const list = Array.from(new Set(branches.map((b) => b.city)));
  const ru: Record<string, string> = { Shahrixon: "Шахрихан", Asaka: "Асака", Andijon: "Андижан" };
  const names = lang === "ru" ? list.map((c) => ru[c] ?? c) : list;
  const and = lang === "ru" ? " и " : " va ";
  return names.length > 1 ? names.slice(0, -1).join(", ") + and + names[names.length - 1] : names[0];
}
const B = branches.length;

const commonFaqUz = [
  {
    q: "Muddatli to'lov uchun qanday hujjatlar kerak?",
    a: "Pasport va plastik kartaning o'zi kifoya — kafil ham, ish joyidan ma'lumotnoma ham kerak emas.",
  },
  {
    q: `Necha oyga bo'lib to'lasa bo'ladi?`,
    a: `${M} oygacha. Oylik to'lov mahsulot narxi va tanlangan muddatga qarab hisoblanadi — aniq summani qo'ng'iroqda yoki filialda aytamiz.`,
  },
  {
    q: "Yetkazib berish bepulmi?",
    a: `${FREE}da yetkazib berish va o'rnatish bepul. Boshqa viloyatlarga ham yetkazamiz — shartlarini qo'ng'iroqda aytamiz.`,
  },
];

const commonFaqRu = [
  {
    q: "Какие документы нужны для рассрочки?",
    a: "Только паспорт и пластиковая карта — без поручителей и без справки с места работы.",
  },
  {
    q: "На сколько месяцев можно оформить рассрочку?",
    a: `До ${M} месяцев. Ежемесячный платёж зависит от цены товара и выбранного срока — точную сумму скажем по телефону или в магазине.`,
  },
  {
    q: "Доставка бесплатная?",
    a: "По Андижанской области доставка и установка бесплатные. В другие регионы Узбекистана тоже доставляем — условия уточним по телефону.",
  },
];

export const topics: Topic[] = [
  {
    id: "texnika",
    path: "/texnika",
    ruPath: "/ru/tehnika",
    photo: {
      src: "/yonalishlar/texnika-900.webp",
      alt: {
        uz: "HAMKOR SAVDO maishiy texnika bo'limi — muzlatgich, kir yuvish mashinasi va oshxona texnikasi",
        ru: "Отдел бытовой техники HAMKOR SAVDO — холодильники, стиральные машины и кухонная техника",
      },
    },
    uz: {
      title: `Maishiy texnika muddatli to'lovga — ${M} oygacha | Shahrixon, Asaka, Andijon | HAMKOR SAVDO`,
      description: `Muzlatgich, kir yuvish mashinasi, oshxona texnikasi va skuterlar ${M} oygacha muddatli to'lovga (nasiyaga). Pasport va plastik karta kifoya. ${FREE}da bepul yetkazish va o'rnatish. ${cities("uz")}da ${B} ta filial.`,
      kicker: "Maishiy texnika",
      h1: "Maishiy texnika muddatli to'lovga",
      lead: `Muzlatgich, kir yuvish mashinasi, oshxona texnikasi va skuterlar — ${M} oygacha bo'lib to'lab olasiz. Rasmiylashtirish uchun pasport va plastik karta kifoya.`,
      points: [
        { title: `${M} oygacha`, text: "To'lovni oylarga bo'lasiz — mahsulotni esa bugun olib ketasiz." },
        { title: "Pasport + karta", text: "Kafil, ma'lumotnoma, uzoq navbat yo'q." },
        { title: "Bepul yetkazish", text: `${FREE}da uyingizgacha olib boramiz va o'rnatib beramiz.` },
        { title: `${B} ta filial`, text: `${cities("uz")}da — texnikani ko'rib, tanlab olasiz.` },
      ],
      body: [
        `HAMKOR SAVDO do'konlarida maishiy texnika bo'limi uyning har bir xonasini qamrab oladi: oshxona uchun muzlatgich va oshxona texnikasi, kir yuvish mashinalari va boshqa uy texnikasi. Shu bo'limda skuterlar ham bor.`,
        `Texnikani ${M} oygacha muddatli to'lovga rasmiylashtiramiz. Buning uchun pasport va plastik kartangiz bilan filialga kelasiz yoki saytda ariza qoldirasiz — mutaxassisimiz qo'ng'iroq qilib, oylik to'lovni hisoblab beradi.`,
        `${FREE} bo'ylab — ${cities("uz")} va atrofidagi tuman va qishloqlarga — texnikani bepul yetkazib beramiz va o'rnatib beramiz. Boshqa viloyatlarga ham yetkazamiz.`,
        `Kerakli model do'konda bo'lmasa, boshqa joyda ko'rgan texnikangizni ham muddatli to'lovga rasmiylashtirib beramiz — nomi va narxini ayting.`,
      ],
      faq: [
        ...commonFaqUz,
        {
          q: "Do'koningizda yo'q texnikani ham muddatli to'lovga olsa bo'ladimi?",
          a: "Ha. Boshqa do'konda yoki internetda ko'rgan modelning nomi va narxini ayting — uni ham muddatli to'lovga rasmiylashtirib beramiz.",
        },
      ],
      cta: "Qaysi texnika kerakligini yozing — oylik to'lovni hisoblab, o'zimiz qo'ng'iroq qilamiz.",
    },
    ru: {
      title: `Бытовая техника в рассрочку до ${M} месяцев — Андижан, Шахрихан, Асака | HAMKOR SAVDO`,
      description: `Холодильники, стиральные машины, кухонная техника и скутеры в рассрочку до ${M} месяцев. Нужны только паспорт и пластиковая карта. Бесплатная доставка и установка по Андижанской области. ${B} магазина: ${cities("ru")}.`,
      kicker: "Бытовая техника",
      h1: "Бытовая техника в рассрочку",
      lead: `Холодильники, стиральные машины, кухонная техника и скутеры — оплата частями до ${M} месяцев. Для оформления нужны только паспорт и пластиковая карта.`,
      points: [
        { title: `До ${M} месяцев`, text: "Платите частями — а технику забираете сегодня." },
        { title: "Паспорт + карта", text: "Без поручителей, справок и долгого ожидания." },
        { title: "Бесплатная доставка", text: "По Андижанской области привезём и установим бесплатно." },
        { title: `${B} магазина`, text: `${cities("ru")} — можно посмотреть и выбрать вживую.` },
      ],
      body: [
        "В магазинах HAMKOR SAVDO отдел бытовой техники охватывает весь дом: холодильники и кухонная техника, стиральные машины и другая техника для дома. В этом же отделе — скутеры.",
        `Технику оформляем в рассрочку до ${M} месяцев. Приходите в магазин с паспортом и пластиковой картой или оставьте заявку на сайте — специалист перезвонит и рассчитает ежемесячный платёж.`,
        `По Андижанской области — ${cities("ru")}, а также районы и сёла вокруг — доставляем и устанавливаем технику бесплатно. В другие регионы Узбекистана тоже доставляем.`,
        "Если нужной модели нет в магазине, оформим в рассрочку и технику, которую вы видели в другом месте, — просто скажите название и цену.",
      ],
      faq: [
        ...commonFaqRu,
        {
          q: "Можно ли взять в рассрочку технику, которой нет у вас в магазине?",
          a: "Да. Назовите модель и цену, которую вы видели в другом магазине или в интернете, — оформим её в рассрочку.",
        },
      ],
      cta: "Напишите, какая техника нужна, — рассчитаем платёж и перезвоним.",
    },
  },
  {
    /* Skuterlar bosh sahifada alohida yo'nalish emas (categories.ts dagi
       2026-09-17 qarori), lekin odamlar "skuter" deb alohida qidiradi —
       shuning uchun qidiruv uchun o'z sahifasi bor. Model, narx, turi
       (elektr/benzin) egasi bermagan — YO'Q. */
    id: "skuter",
    path: "/skuter",
    ruPath: "/ru/skutery",
    uz: {
      title: `Skuter muddatli to'lovga — ${M} oygacha | Shahrixon, Asaka, Andijon | HAMKOR SAVDO`,
      description: `Skuterlar ${M} oygacha muddatli to'lovga (nasiya, rassrochka). Pasport va plastik karta kifoya, kafilsiz. ${FREE}da bepul yetkazib berish. ${cities("uz")}da ${B} ta filial.`,
      kicker: "Skuterlar",
      h1: "Skuter muddatli to'lovga",
      lead: `Skuterni bugun minib ketasiz, pulini ${M} oygacha bo'lib to'laysiz. Rasmiylashtirish uchun pasport va plastik karta kifoya.`,
      points: [
        { title: `${M} oygacha`, text: "Skuterni hozir olasiz, to'lov oylarga bo'linadi." },
        { title: "Pasport + karta", text: "Kafil va ma'lumotnoma kerak emas." },
        { title: "Bepul yetkazish", text: `${FREE}da uyingizgacha olib boramiz.` },
        { title: "Ko'rib tanlash", text: `${cities("uz")}dagi filiallarda skuterni jonli ko'rasiz.` },
      ],
      body: [
        "HAMKOR SAVDO do'konlarida skuterlar maishiy texnika bo'limida sotiladi. Ishga, o'qishga, bozorga yoki qishloq ichida yurish uchun skuterni filialda ko'rib, o'zingizga mosini tanlaysiz.",
        `Skuterni ${M} oygacha muddatli to'lovga (rassrochka) rasmiylashtiramiz. Pasport va plastik kartangiz bilan filialga keling yoki saytda ariza qoldiring — mutaxassisimiz qo'ng'iroq qilib, oylik to'lovni hisoblab beradi.`,
        `${FREE} bo'ylab — ${cities("uz")} va atrofidagi tuman va qishloqlarga — bepul yetkazib beramiz. Boshqa viloyatlarga ham yetkazamiz, shartlarini qo'ng'iroqda aytamiz.`,
        "Kerakli skuter do'konda bo'lmasa, boshqa joyda ko'rgan modelingizni ham muddatli to'lovga rasmiylashtirib beramiz — nomi va narxini ayting.",
      ],
      faq: [
        ...commonFaqUz,
        {
          q: "Qaysi skuterlar borligini qanday bilaman?",
          a: "Qo'ng'iroq qiling yoki ariza qoldiring — hozir qaysi filialda qanday skuterlar borligini va oylik to'lovini aytib beramiz.",
        },
      ],
      cta: "Qanday skuter kerakligini yozing — bor modellar va oylik to'lovni aytib, o'zimiz qo'ng'iroq qilamiz.",
    },
    ru: {
      title: `Скутеры в рассрочку до ${M} месяцев — Андижан, Шахрихан, Асака | HAMKOR SAVDO`,
      description: `Скутеры в рассрочку до ${M} месяцев: только паспорт и пластиковая карта, без поручителей. Бесплатная доставка по Андижанской области. ${B} магазина: ${cities("ru")}.`,
      kicker: "Скутеры",
      h1: "Скутеры в рассрочку",
      lead: `Скутер забираете сегодня, а платите частями до ${M} месяцев. Для оформления нужны только паспорт и пластиковая карта.`,
      points: [
        { title: `До ${M} месяцев`, text: "Скутер сейчас — оплата частями по месяцам." },
        { title: "Паспорт + карта", text: "Без поручителей и справок." },
        { title: "Бесплатная доставка", text: "По Андижанской области привезём до дома." },
        { title: "Выбор вживую", text: `Посмотреть скутеры можно в магазинах: ${cities("ru")}.` },
      ],
      body: [
        "В магазинах HAMKOR SAVDO скутеры продаются в отделе бытовой техники. Для работы, учёбы, базара или поездок по посёлку — скутер можно посмотреть в магазине и выбрать подходящий.",
        `Скутеры оформляем в рассрочку до ${M} месяцев. Приходите с паспортом и пластиковой картой или оставьте заявку на сайте — специалист перезвонит и рассчитает ежемесячный платёж.`,
        `По Андижанской области — ${cities("ru")}, а также районы и сёла вокруг — доставляем бесплатно. В другие регионы тоже доставляем, условия уточним по телефону.`,
        "Если нужного скутера нет в магазине, оформим в рассрочку и модель, которую вы видели в другом месте, — просто скажите название и цену.",
      ],
      faq: [
        ...commonFaqRu,
        {
          q: "Как узнать, какие скутеры есть в наличии?",
          a: "Позвоните или оставьте заявку — скажем, какие скутеры сейчас есть в каком магазине и сколько будет ежемесячный платёж.",
        },
      ],
      cta: "Напишите, какой скутер нужен, — скажем, что есть в наличии, и перезвоним.",
    },
  },
  {
    id: "mebel",
    path: "/mebel",
    ruPath: "/ru/mebel",
    photo: {
      src: "/yonalishlar/mebel-900.webp",
      alt: {
        uz: "HAMKOR SAVDO mebel bo'limi — oshxona stoli va o'rindiqlar to'plami",
        ru: "Отдел мебели HAMKOR SAVDO — кухонный стол со стульями",
      },
    },
    uz: {
      title: `Mebel muddatli to'lovga — ${M} oygacha | Shahrixon, Asaka, Andijon | HAMKOR SAVDO`,
      description: `Yotoqxonadan oshxonagacha — uy uchun mebel ${M} oygacha muddatli to'lovga (nasiyaga). Pasport va plastik karta kifoya. ${FREE}da bepul yetkazish va o'rnatish. ${cities("uz")}da ${B} ta filial.`,
      kicker: "Mebel",
      h1: "Mebel muddatli to'lovga",
      lead: `Yotoqxonadan oshxonagacha — uyni butunlay jihozlash uchun mebel. ${M} oygacha bo'lib to'laysiz, rasmiylashtirish uchun pasport va plastik karta kifoya.`,
      points: [
        { title: `${M} oygacha`, text: "Uyni bir yo'la jihozlaysiz, to'lov esa oylarga bo'linadi." },
        { title: "Pasport + karta", text: "Kafil va ma'lumotnoma kerak emas." },
        { title: "Bepul yetkazish", text: `${FREE}da olib boramiz va joyiga o'rnatib beramiz.` },
        { title: "Ko'rib tanlash", text: `${cities("uz")}dagi filiallarda mebelni jonli ko'rasiz.` },
      ],
      body: [
        "Yangi uyga ko'chish, to'y yoki ta'mirdan keyin uyni jihozlash — katta xarajat. HAMKOR SAVDO'da yotoqxonadan oshxonagacha mebelni bir manzilda tanlab, to'lovini oylarga bo'lasiz.",
        `Mebel ${M} oygacha muddatli to'lovga rasmiylashtiriladi. Pasport va plastik kartangiz bilan filialga keling yoki ariza qoldiring — oylik to'lovni hisoblab beramiz.`,
        `${FREE} bo'ylab mebelni bepul yetkazib beramiz va o'rnatib beramiz. Boshqa viloyatlarga ham yetkazamiz — shartlarini qo'ng'iroqda aytamiz.`,
        "Sepga mebel, sovchilik uchun to'plam yoki bitta divan — qaysi biri kerakligini ayting, do'kon jamoasi tanlashga yordam beradi.",
      ],
      faq: [
        ...commonFaqUz,
        {
          q: "Mebelni do'konga kelmasdan tanlasa bo'ladimi?",
          a: "Ha. Ariza qoldiring yoki qo'ng'iroq qiling — rasmlarini yuborib, telefon orqali tanlashga yordam beramiz va yetkazib beramiz.",
        },
      ],
      cta: "Qaysi xona uchun mebel kerakligini yozing — o'zimiz qo'ng'iroq qilamiz.",
    },
    ru: {
      title: `Мебель в рассрочку до ${M} месяцев — Андижан, Шахрихан, Асака | HAMKOR SAVDO`,
      description: `Мебель для дома — от спальни до кухни — в рассрочку до ${M} месяцев. Только паспорт и пластиковая карта. Бесплатная доставка и установка по Андижанской области. ${B} магазина: ${cities("ru")}.`,
      kicker: "Мебель",
      h1: "Мебель в рассрочку",
      lead: `От спальни до кухни — мебель, чтобы обставить весь дом. Оплата частями до ${M} месяцев, для оформления нужны только паспорт и пластиковая карта.`,
      points: [
        { title: `До ${M} месяцев`, text: "Обставляете дом сразу, а платите частями." },
        { title: "Паспорт + карта", text: "Без поручителей и справок." },
        { title: "Бесплатная доставка", text: "По Андижанской области привезём и установим." },
        { title: "Выбор вживую", text: `Посмотреть мебель можно в магазинах: ${cities("ru")}.` },
      ],
      body: [
        "Переезд, свадьба или ремонт — обставить дом сразу дорого. В HAMKOR SAVDO мебель для дома — от спальни до кухни — можно выбрать в одном месте и оплатить частями.",
        `Мебель оформляем в рассрочку до ${M} месяцев. Приходите с паспортом и пластиковой картой или оставьте заявку — рассчитаем ежемесячный платёж.`,
        "По Андижанской области доставляем и устанавливаем мебель бесплатно. В другие регионы тоже доставляем — условия уточним по телефону.",
        "Мебель в приданое, комплект к сватовству или один диван — скажите, что нужно, и сотрудники магазина помогут выбрать.",
      ],
      faq: [
        ...commonFaqRu,
        {
          q: "Можно выбрать мебель, не приезжая в магазин?",
          a: "Да. Оставьте заявку или позвоните — пришлём фото, поможем выбрать по телефону и доставим.",
        },
      ],
      cta: "Напишите, для какой комнаты нужна мебель, — мы перезвоним.",
    },
  },
  {
    id: "tilla",
    path: "/tilla",
    ruPath: "/ru/zoloto",
    photo: {
      src: "/yonalishlar/tilla-900.webp",
      alt: {
        uz: "HAMKOR SAVDO zargarlik bo'limi — shisha peshtaxtada tilla taqinchoqlar",
        ru: "Ювелирный отдел HAMKOR SAVDO — золотые украшения на витрине",
      },
    },
    uz: {
      title: `Tilla taqinchoqlar muddatli to'lovga — ${M} oygacha | Shahrixon, Asaka, Andijon | HAMKOR SAVDO`,
      description: `Sovchilik, to'y va bayram uchun tilla taqinchoqlar ${M} oygacha muddatli to'lovga (nasiyaga). Pasport va plastik karta kifoya. Do'konda ko'rib, taqqoslab tanlaysiz. ${cities("uz")}da ${B} ta filial.`,
      kicker: "Tilla",
      h1: "Tilla taqinchoqlar muddatli to'lovga",
      lead: `Sovchilik, to'y, tug'ilgan kun — muhim kunlar uchun tilla taqinchoqlar. ${M} oygacha bo'lib to'laysiz, rasmiylashtirish uchun pasport va plastik karta kifoya.`,
      points: [
        { title: `${M} oygacha`, text: "Muhim kun kutib turmaydi — to'lovni keyin bo'lib to'laysiz." },
        { title: "Pasport + karta", text: "Kafil va ma'lumotnoma kerak emas." },
        { title: "Ko'rib tanlash", text: "Har bir buyumni peshtaxtada ko'rib, taqqoslaysiz." },
        { title: `${B} ta filial`, text: `${cities("uz")}da zargarlik bo'limlari.` },
      ],
      body: [
        "HAMKOR SAVDO'ning zargarlik bo'limida sovchilik, nikoh to'yi, beshik to'yi va tug'ilgan kunlar uchun tilla taqinchoqlar bor. Har bir buyumni do'konning o'zida ko'rib, bir-biri bilan taqqoslab tanlaysiz.",
        `Tilla taqinchoqlarni ham ${M} oygacha muddatli to'lovga rasmiylashtiramiz — pasport va plastik karta kifoya. Oylik to'lovni filialda yoki qo'ng'iroqda aniq hisoblab beramiz.`,
        `Zargarlik bo'limlari ${cities("uz")}dagi filiallarimizda. Qaysi filialga kelishingizni tanlang yoki ariza qoldiring — sizga qulay vaqtni kelishib olamiz.`,
      ],
      faq: [
        ...commonFaqUz.slice(0, 2),
        {
          q: "Tillani ko'rmasdan olsa bo'ladimi?",
          a: "Taqinchoqni o'zingiz ko'rib tanlashingizni tavsiya qilamiz — shuning uchun filialga kelasiz. Ariza qoldirsangiz, qaysi filialda nima borligini oldindan aytib beramiz.",
        },
      ],
      cta: "Qanday taqinchoq qidirayotganingizni yozing — qaysi filialda borligini aytamiz.",
    },
    ru: {
      title: `Золотые украшения в рассрочку до ${M} месяцев — Андижан, Шахрихан, Асака | HAMKOR SAVDO`,
      description: `Золото к сватовству, свадьбе и праздникам в рассрочку до ${M} месяцев. Только паспорт и пластиковая карта. Выбираете вживую на витрине. ${B} магазина: ${cities("ru")}.`,
      kicker: "Золото",
      h1: "Золотые украшения в рассрочку",
      lead: `Сватовство, свадьба, день рождения — золотые украшения к важным дням. Оплата частями до ${M} месяцев, нужны только паспорт и пластиковая карта.`,
      points: [
        { title: `До ${M} месяцев`, text: "Важный день не ждёт — платите частями потом." },
        { title: "Паспорт + карта", text: "Без поручителей и справок." },
        { title: "Выбор вживую", text: "Каждое украшение можно посмотреть и сравнить на витрине." },
        { title: `${B} магазина`, text: `Ювелирные отделы: ${cities("ru")}.` },
      ],
      body: [
        "В ювелирном отделе HAMKOR SAVDO — золотые украшения к сватовству, свадьбе, бешик-тою и дням рождения. Каждое изделие можно посмотреть в магазине и сравнить с другими.",
        `Золото тоже оформляем в рассрочку до ${M} месяцев — достаточно паспорта и пластиковой карты. Точный ежемесячный платёж рассчитаем в магазине или по телефону.`,
        `Ювелирные отделы есть в наших магазинах: ${cities("ru")}. Выберите удобный магазин или оставьте заявку — договоримся об удобном времени.`,
      ],
      faq: [
        ...commonFaqRu.slice(0, 2),
        {
          q: "Можно купить золото, не посмотрев его?",
          a: "Мы советуем выбирать украшение вживую — поэтому приглашаем в магазин. Оставьте заявку, и мы заранее скажем, что есть в каком магазине.",
        },
      ],
      cta: "Напишите, какое украшение ищете, — скажем, в каком магазине оно есть.",
    },
  },
  {
    id: "muddatli-tolov",
    path: "/muddatli-tolov",
    ruPath: "/ru/rassrochka",
    uz: {
      title: `Muddatli to'lov va nasiya (rassrochka) ${M} oygacha — pasport va karta bilan | Andijon viloyati | HAMKOR SAVDO`,
      description: `Texnika, mebel va tillani ${M} oygacha muddatli to'lovga (nasiyaga) oling: faqat pasport va plastik karta, kafilsiz. ${N} dan ortiq mahsulot, ${FREE}da bepul yetkazish. ${cities("uz")}da ${B} ta filial.`,
      kicker: "Muddatli to'lov",
      h1: `Muddatli to'lov — ${M} oygacha, pasport va karta bilan`,
      lead: `Maishiy texnika, mebel va tilla taqinchoqlarni bugun olib ketasiz, to'lovni ${M} oygacha bo'lib to'laysiz. Kafil ham, ma'lumotnoma ham kerak emas — pasport va plastik karta kifoya.`,
      points: [
        { title: `${M} oygacha`, text: "Muddatni o'zingiz tanlaysiz — oylik to'lov shunga qarab hisoblanadi." },
        { title: "Faqat 2 ta hujjat", text: "Pasport va plastik karta. Kafil va ma'lumotnomasiz." },
        { title: `${N} mahsulot`, text: "Texnika, mebel, tilla — hammasi bitta do'konda." },
        { title: "Yo'q mahsulot ham", text: "Boshqa joyda ko'rganingizni ham rasmiylashtiramiz." },
      ],
      body: [
        "Muddatli to'lov (xalq tilida — nasiya yoki rassrochka) — mahsulotni hozir olib, pulini oylarga bo'lib to'lash. HAMKOR SAVDO'da bu uch yo'nalishning hammasiga amal qiladi: maishiy texnika, mebel va tilla taqinchoqlar.",
        `Qanday ishlaydi: mahsulotni tanlaysiz, pasport va plastik kartangiz bilan joyida rasmiylashtiramiz, xaridingizni shu kuniyoq olib ketasiz yoki yetkazib beramiz — to'lovni esa ${M} oygacha bo'lib to'laysiz.`,
        "Oylik to'lov mahsulot narxi va tanlangan muddatga qarab hisoblanadi. Aniq summani bilish uchun ariza qoldiring yoki qo'ng'iroq qiling — mutaxassisimiz hisoblab beradi.",
        "Kerakli mahsulot do'konda bo'lmasa ham qaytib ketmaysiz: boshqa do'konda, bozorda yoki internetda ko'rgan mahsulotingizni ham muddatli to'lovga rasmiylashtirib beramiz.",
      ],
      faq: [
        ...commonFaqUz,
        {
          q: "Nasiya bilan muddatli to'lovning farqi bormi?",
          a: `Xalq orasida muddatli to'lovni nasiya yoki rassrochka deyishadi — gap bitta narsa haqida: mahsulotni hozir olib, pulini ${M} oygacha bo'lib to'laysiz. Pasport va plastik karta kifoya.`,
        },
        {
          q: "Boshqa joyda ko'rgan mahsulotni ham muddatli to'lovga olsam bo'ladimi?",
          a: "Ha. Mahsulotning nomi, narxi va qayerdaligini ayting — uni ham muddatli to'lovga rasmiylashtirib beramiz.",
        },
        {
          q: "Filialga kelmasdan rasmiylashtirsa bo'ladimi?",
          a: "Ariza qoldiring yoki qo'ng'iroq qiling — mahsulotni telefon orqali tanlaysiz, rasmiylashtirish tartibini va yetkazib berishni kelishib olamiz.",
        },
      ],
      cta: "Nima olmoqchi ekaningizni yozing — oylik to'lovni hisoblab, qo'ng'iroq qilamiz.",
    },
    ru: {
      title: `Рассрочка до ${M} месяцев по паспорту и карте — Андижанская область | HAMKOR SAVDO`,
      description: `Техника, мебель и золото в рассрочку до ${M} месяцев: только паспорт и пластиковая карта, без поручителей. Более ${N.replace("+", "")} товаров, бесплатная доставка по Андижанской области. ${B} магазина: ${cities("ru")}.`,
      kicker: "Рассрочка",
      h1: `Рассрочка до ${M} месяцев — по паспорту и карте`,
      lead: `Бытовую технику, мебель и золотые украшения забираете сегодня, а платите частями до ${M} месяцев. Без поручителей и справок — нужны только паспорт и пластиковая карта.`,
      points: [
        { title: `До ${M} месяцев`, text: "Срок выбираете сами — платёж рассчитывается под него." },
        { title: "Всего 2 документа", text: "Паспорт и пластиковая карта. Без поручителей и справок." },
        { title: `${N} товаров`, text: "Техника, мебель, золото — всё в одном магазине." },
        { title: "Даже чего нет у нас", text: "Оформим и товар, который вы видели в другом месте." },
      ],
      body: [
        "Рассрочка (в народе — насия) — это когда товар вы забираете сейчас, а платите частями по месяцам. В HAMKOR SAVDO она действует на все три направления: бытовую технику, мебель и золотые украшения.",
        `Как это работает: выбираете товар, мы оформляем рассрочку на месте по паспорту и пластиковой карте, покупку забираете в тот же день или мы её доставляем — а оплачиваете частями до ${M} месяцев.`,
        "Ежемесячный платёж зависит от цены товара и срока. Чтобы узнать точную сумму, оставьте заявку или позвоните — специалист рассчитает.",
        "Если нужного товара нет в магазине, вы не уйдёте ни с чем: оформим в рассрочку и товар, который вы видели в другом магазине, на базаре или в интернете.",
      ],
      faq: [
        ...commonFaqRu,
        {
          q: "Можно оформить в рассрочку товар из другого магазина?",
          a: "Да. Скажите название, цену и где вы его видели — оформим в рассрочку.",
        },
        {
          q: "Можно оформить, не приезжая в магазин?",
          a: "Оставьте заявку или позвоните — выберете товар по телефону, а порядок оформления и доставку мы согласуем.",
        },
      ],
      cta: "Напишите, что хотите купить, — рассчитаем платёж и перезвоним.",
    },
  },
];

export const topicById = (id: TopicId) => topics.find((t) => t.id === id)!;
