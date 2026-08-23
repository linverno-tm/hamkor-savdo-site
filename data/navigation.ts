/** Primary section navigation — anchors into the single-page story. */

export interface NavItem {
  href: string;
  label: string;
}

export const navigation: NavItem[] = [
  { href: "/#biz-haqimizda", label: "Biz haqimizda" },
  { href: "/#yonalishlar", label: "Yo'nalishlar" },
  { href: "/#afzalliklar", label: "Afzalliklar" },
  { href: "/#muddatli-tolov", label: "Muddatli to'lov" },
  { href: "/#filiallar", label: "Filiallar" },
  { href: "/#ariza", label: "Ariza qoldirish" },
  { href: "/#aloqa", label: "Aloqa" },
];
