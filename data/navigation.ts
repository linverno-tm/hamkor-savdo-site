/**
 * Asosiy menyu — alohida sahifalarga.
 *
 * Ilgari bu yerda bosh sahifa ichidagi langarlar edi ("/#biz-haqimizda",
 * "/#aloqa" ...). Odam uchun farqi kam, lekin Google langarni alohida
 * bo'lim deb hisoblamaydi: qidiruv natijasidagi sayt ostidagi bo'limlar
 * (sitelinks) faqat haqiqiy sahifalardan tuziladi va ular aynan menyudagi
 * havolalardan tanlanadi. Endi menyuning har bir bandi o'z sahifasi.
 */

export interface NavItem {
  href: string;
  label: string;
}

export const navigation: NavItem[] = [
  { href: "/texnika", label: "Maishiy texnika" },
  { href: "/mebel", label: "Mebel" },
  { href: "/tilla", label: "Tilla" },
  { href: "/muddatli-tolov", label: "Muddatli to'lov" },
  { href: "/filiallar", label: "Filiallar" },
  { href: "/aloqa", label: "Aloqa" },
];
