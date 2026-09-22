/**
 * Asosiy menyu — alohida sahifalarga.
 *
 * Google qidiruv natijasidagi sayt ostidagi bo'limlarni (sitelinks) faqat
 * haqiqiy sahifalardan tuzadi va ularni menyudagi havolalardan tanlaydi.
 * Shuning uchun "Katalog" ochiluvchi ro'yxat bo'lsa ham, ichidagi havolalar
 * sahifa HTML'ida doim turadi (faqat ko'rinishi yashiringan).
 */

export interface NavItem {
  href: string;
  label: string;
}

/** "Katalog" ichidagi yo'nalishlar. */
export const catalogNav: NavItem[] = [
  { href: "/texnika", label: "Maishiy texnika" },
  { href: "/tilla", label: "Tilla" },
  { href: "/mebel", label: "Mebel" },
  { href: "/skuter", label: "Skuterlar" },
];

/** Katalogdan keyingi bandlar. */
export const mainNav: NavItem[] = [
  { href: "/muddatli-tolov", label: "Muddatli to'lov" },
  { href: "/filiallar", label: "Filiallar" },
  { href: "/#biz-haqimizda", label: "Biz haqimizda" },
  { href: "/aloqa", label: "Aloqa" },
];

/** Tekis ro'yxat — footer va boshqa joylar uchun. */
export const navigation: NavItem[] = [...catalogNav, ...mainNav];
