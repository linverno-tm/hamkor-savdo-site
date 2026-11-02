/**
 * Yuklanish qatlami — rasm/video kelguncha idish ustida yumshoq nur va sariq
 * "deraza chirog'i" chizig'i (app/yukl.css). Idishga `yk` sinfi qo'yiladi va
 * shu element birinchi bola bo'ladi: rasm kelgach u qatlam ustini o'zi yopadi,
 * lib/motion.ts esa idishga data-yk="ok" yozib, qatlamni o'chiradi. Videoda
 * chiziq yuklangan foizni ko'rsatadi (--p).
 *
 * Server HTML'ida bor — skript DOM'ga tugun qo'shmaydi, React hidratsiyasi
 * bilan to'qnashmaydi.
 */
export function Yukl() {
  return (
    <span className="yk-l" aria-hidden="true">
      <i />
    </span>
  );
}
