/**
 * A/B sinovlar — qaysi variant ko'proq ariza keltirishini raqam bilan bilish.
 *
 * Qanday ishlaydi:
 *  - Har faol sinov uchun mehmon birinchi kirishda tasodifan "a" yoki "b"
 *    guruhga tushadi (localStorage, keyingi kirishlarda ham o'sha guruh).
 *  - Guruh `<html data-ab-<id>="a|b">` ga yoziladi — sahifa chizilishidan
 *    OLDIN (<head> dagi skript), shuning uchun B guruhdagi odam bir lahza
 *    ham A ni ko'rmaydi. Ikkala variant HTML'da bor, CSS keraksizini yashiradi:
 *    JavaScript'siz va kirillcha nusxada ham ishlaydi.
 *  - Metrika'ga `params: {ab: {<id>: "a"}}` yuboriladi (layout.tsx), arizaga
 *    esa `src_info` boshida `ab=<id>:<v>` — admin panel Statistika'da har
 *    guruhdan nechta ariza kelganini Metrika'siz ham sanaydi.
 *  - Ko'rib chiqish: `?ab=<id>:b` — sinov yoqilmagan bo'lsa ham shu variant
 *    ko'rsatiladi (saqlanmaydi, statistikaga kirmaydi).
 *
 * Har sinov faqat egasi ikkala variantni ko'rib tasdiqlagandan keyin
 * `active: true` qilinadi (egasi 22.10: matn/joylashuv uning ruxsatisiz
 * o'zgarmasin).
 */
export interface Experiment {
  id: string;
  active: boolean;
  /** A va B nima — admin panelda va kodda tushunarli bo'lsin. */
  a: string;
  b: string;
}

export const EXPERIMENTS: Experiment[] = [
  {
    id: "tel-forma",
    // Egasi 22.10 ikkala variantni ko'rib tasdiqladi va yoqdi.
    active: true,
    a: "Telefonda qisqa ariza formasi yo'q (faqat pastdagi to'liq forma)",
    b: "Telefonda qisqa ariza formasi bosh qismda, tugmalardan keyin",
  },
];

const ACTIVE = EXPERIMENTS.filter((e) => e.active).map((e) => e.id);
const ALL = EXPERIMENTS.map((e) => e.id);

/** <head> dagi skript (data-keep — kirillcha nusxada ham qoladi). */
export const AB_SCRIPT = `(function(){try{
var d=document.documentElement,act=${JSON.stringify(ACTIVE)},all=${JSON.stringify(ALL)},s={},tag=[];
var q=/[?&]ab=([a-z0-9-]+):([ab])/.exec(location.search);
if(q&&all.indexOf(q[1])>=0){d.setAttribute('data-ab-'+q[1],q[2]);}
act.forEach(function(id){
  if(q&&q[1]===id)return;
  var k='hs_ab_'+id,v=null;try{v=localStorage.getItem(k);}catch(e){}
  if(v!=='a'&&v!=='b'){v=Math.random()<0.5?'a':'b';try{localStorage.setItem(k,v);}catch(e){}}
  d.setAttribute('data-ab-'+id,v);s[id]=v;tag.push(id+':'+v);
});
if(tag.length){window.__hsAb=s;window.__hsAbTag=tag.join(',');}
}catch(e){}})();`;
