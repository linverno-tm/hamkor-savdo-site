import { Logo } from "@/components/ui/Logo";

/**
 * The frame a card shows while it has no photograph.
 *
 * Avvalgi variant "skeleton" edi — och fon ustidan sekin o'tadigan yorug'lik
 * to'lqini, brauzerda kontent yuklanayotganini bildiradigan naqsh. Ikkita
 * kamchiligi bor edi: u yolg'on gapiradi (hech narsa yuklanmayapti, surat
 * shunchaki yo'q), va suratli kartochkalar yonida turganda bo'sh o'ra bo'lib
 * ko'rinadi — sahifa buzilgandek.
 *
 * Endi bu o'rin brend bloki: to'q binafsha fon va oq belgi. Sayt bo'ylab
 * shunday bloklar allaqachon bor (bosh sahifadagi "Qisqacha" paneli,
 * "Rahmat" sahifasi), shuning uchun u kutilmagan narsa emas — ataylab
 * shunday qilingandek ko'rinadi. Filial kartochkasi ostiga mo'ljalini
 * yozadi, ya'ni bo'sh joy ma'lumotga aylanadi.
 */
export function PhotoPlaceholder({
  className,
  caption,
}: {
  className?: string;
  caption?: string;
}) {
  return (
    <span
      className={`on-purple relative flex w-full items-center justify-center overflow-hidden bg-purple px-5 text-center ${className ?? ""}`}
    >
      {/* Bitta belgi — avval orqa fonda kattasi ham bor edi, lekin ikkalasi
          bir markazda turgani uchun yozuv uning ustiga tushib, chalkash
          ko'rinardi. */}
      <span className="flex flex-col items-center gap-3">
        <Logo variant="mark" className="h-14 w-auto text-white/90" />
        {caption ? (
          <span className="text-sm leading-snug text-on-purple-2">{caption}</span>
        ) : null}
      </span>
    </span>
  );
}
