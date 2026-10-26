/**
 * Saytning harakat tizimi — kutubxonasiz, React'siz.
 *
 * Nega React emas: kirillcha nusxa (tools/uz-kr-build.mjs) React
 * skriptlarini olib tashlaydi, faqat `data-keep` skriptlar qoladi. Shu
 * skript ikkala nusxada ham bir xil ishlaydi.
 *
 * Nima qiladi:
 *  - `[data-m]` — ekranga kirganda `.m-in` oladi; harakatning o'zi CSS da
 *    (app/home.css). `[data-m-stagger]` bolalari navbat bilan chiqadi
 *    (tartib CSS da, nth-child — DOM'ga tegilmaydi, React bilan to'qnashmaydi).
 *  - `[data-count]` — ko'rinishga kirganda raqam sanaladi; server matni
 *    oxirgi qiymatning o'zi, ya'ni JS bo'lmasa ham to'g'ri.
 *  - `[data-site-header]` — sahifa siljigach `data-scrolled`.
 *  - `.action-bar` (telefondagi pastki qator) — bosh qismdan o'tgach
 *    chiqadi, ariza formasi ko'rinib turganda yashirinadi.
 *  - `[data-ask]` ("Shuni so'rash") — bosilganda ariza formasidagi
 *    "Nima qiziqtiradi?" maydoniga taklif nomi yoziladi, ism maydoni tanlanadi.
 *  - `[data-open-hours]` — filialning "hozir ochiq/yopiq" belgisi, har daqiqa
 *    (harakat sozlamasidan qat'i nazar).
 *  - `[data-spot]` — sichqoncha bilan: kursor o'rni `--mx`/`--my` ga
 *    yoziladi (sahnadagi yorug'lik). Barmoq bilan ishlatilmaydi.
 *
 * Xavfsizlik: yashirish `.m-js` sinfiga bog'langan va u faqat shu skript
 * ishlaganda qo'yiladi. Kamaytirilgan harakat sozlamasida qo'yilmaydi.
 * Biror sabab bilan kuzatuvchi ishlamasa, 4 soniyadan keyin hammasi
 * baribir ko'rinadi.
 */
import { H3D_SRC } from "@/lib/vitrina";

export const MOTION_SCRIPT = `(function(){try{
var d=document,de=d.documentElement;
/* "Shuni so'rash" — harakat sozlamasidan qat'i nazar doim ishlaydi. */
d.addEventListener('click',function(e){
  var a=e.target.closest&&e.target.closest('[data-ask]');if(!a)return;
  var note=d.getElementById('lead-note');if(!note)return;
  note.value=a.getAttribute('data-ask');
  var f=note.closest('form');if(f){f.classList.remove('is-asked');void f.offsetWidth;f.classList.add('is-asked');}
  setTimeout(function(){var n=d.getElementById('lead-name');if(n)n.focus({preventScroll:true});},700);
});
/* 3D (tools/hero3d/hamkor3d.js — bosh qism sahnasi va 3D vitrina): sahifa to'liq
   ochilib, brauzer bo'shaganda bir marta yuklanadi — skroll paytida emas (egasi 26.10:
   "scroll payti qotishlar"). Bosh qism bezagi trafik tejash, kuchsiz qurilma yoki
   kamaytirilgan harakatda o'chiq (window.__h3dHero=false); vitrina WebGL bo'lsa ishlaydi,
   bo'lmasa rasmlar qoladi. */
function h3d(){
  if(!d.querySelector('[data-hero3d],[data-3d]'))return;
  try{var g=d.createElement('canvas');if(!(g.getContext('webgl2')||g.getContext('webgl')))return;}catch(e){return;}
  var c=navigator.connection||{},mem=navigator.deviceMemory||4,cores=navigator.hardwareConcurrency||4;
  var lite=c.saveData||/(^|-)2g/.test(c.effectiveType||'');
  if(lite&&!d.querySelector('[data-3d]'))return;
  window.__h3dHero=!(lite||mem<3||cores<4||(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches));
  var go=function(){var s=d.createElement('script');s.type='module';s.src='${H3D_SRC}';d.head.appendChild(s);};
  var idle=function(){(window.requestIdleCallback||function(f){setTimeout(f,600);})(go,{timeout:2500});};
  if(d.readyState==='complete')idle();else addEventListener('load',idle);
}
if(d.readyState==='loading')d.addEventListener('DOMContentLoaded',h3d);else h3d();
/* Turgan sahifaning o'ziga olib boruvchi havola (bosh sahifada logotip) —
   sahifa eng tepasiga aylanadi (egasi 23.10). Next'ning Link'i bunda
   joyidan qimirlamasdi. Capture bosqichida — React ko'rmasdan oldin. */
function noSlash(p){return p.length>1&&p.charAt(p.length-1)==='/'?p.slice(0,-1):p;}
d.addEventListener('click',function(e){
  if(e.button||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;
  var a=e.target.closest&&e.target.closest('a[href]');
  if(!a||a.target==='_blank'||a.hash||a.origin!==location.origin||noSlash(a.pathname)!==noSlash(location.pathname))return;
  e.preventDefault();e.stopPropagation();
  var mm=d.querySelector('details.mobile-menu[open]');if(mm)mm.removeAttribute('open');
  var rm=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
  window.scrollTo({top:0,behavior:rm?'auto':'smooth'});
  if(location.hash)history.replaceState(history.state,'',location.pathname+location.search);
},true);
/* Mijoz lavhasi — Telegram o'yinchisi shu sahifada ochiladi (data-lavha).
   Manzil build'da yozilgan o'z kanalimiz posti. Ctrl/Shift bilan — odatdagidek yangi oynada. */
d.addEventListener('click',function(e){
  var a=e.target.closest&&e.target.closest('[data-lavha]');
  if(!a||e.ctrlKey||e.metaKey||e.shiftKey||e.button)return;
  e.preventDefault();
  var box=d.createElement('div');box.className='lv-box';box.setAttribute('role','dialog');box.setAttribute('aria-modal','true');
  box.innerHTML='<button type="button" class="lv-x" aria-label="×">×</button><div class="lv-frame"><iframe src="'+a.getAttribute('data-lavha')+'?embed=1&mode=tme" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="Telegram"></iframe></div>';
  function close(){box.remove();de.style.overflow='';removeEventListener('keydown',key);a.focus({preventScroll:true});}
  function key(k){if(k.key==='Escape')close();}
  box.addEventListener('click',function(k){if(k.target===box||k.target.classList.contains('lv-x'))close();});
  addEventListener('keydown',key);
  d.body.appendChild(box);de.style.overflow='hidden';box.querySelector('.lv-x').focus();
});
/* Filial holati (components/ui/OpenStatus.tsx) — Toshkent vaqti (UTC+5).
   Matn atributga yoziladi (CSS ::after ko'rsatadi): <span> ichiga matn
   qo'yilsa React hidratsiyada mos kelmaslik ko'rib, sahifani qayta chizadi. */
function openSt(){
  var L=de.lang||'uz',T=L==='uz-Cyrl'?['Ҳозир очиқ · {c} гача','Тез ёпилади · {c} да','Ёпиқ · {o} да очилади','Ёпиқ · эртага {o} да очилади']
    :L==='ru'?['Сейчас открыто · до {c}','Скоро закроется · в {c}','Закрыто · откроется в {o}','Закрыто · откроется завтра в {o}']
    :['Hozir ochiq · {c} gacha','Tez yopiladi · {c} da','Yopiq · {o} da ochiladi','Yopiq · ertaga {o} da ochiladi'];
  var n=new Date(Date.now()+5*36e5),now=n.getUTCHours()*60+n.getUTCMinutes();
  d.querySelectorAll('[data-open-hours]').forEach(function(el){
    var m=/(\\d{1,2}):(\\d{2})\\D+(\\d{1,2}):(\\d{2})/.exec(el.getAttribute('data-open-hours'));if(!m)return;
    var o=+m[1]*60+ +m[2],c=+m[3]*60+ +m[4],os=m[1]+':'+m[2],cs=m[3]+':'+m[4],st,t;
    if(now>=o&&now<c){st=c-now<=60?'soon':'open';t=T[st==='soon'?1:0];}
    else{st='closed';t=T[now<o?2:3];}
    t=t.replace('{c}',cs).replace('{o}',os);el.setAttribute('data-state',st);el.setAttribute('data-text',t);el.setAttribute('aria-label',t);
  });
}
/* React bor sahifada — u sahifani jonlantirib bo'lgach (load + biroz), aks
   holda (kirillcha nusxa) — darhol. */
function startSt(){openSt();setInterval(openSt,60000);}
function pickSt(){if(d.querySelector('script[src*="/_next/"]')){if(d.readyState==='complete')setTimeout(startSt,400);else addEventListener('load',function(){setTimeout(startSt,400);});}else startSt();}
if(d.readyState==='loading')d.addEventListener('DOMContentLoaded',pickSt);else pickSt();
if(!('IntersectionObserver' in window))return;
if(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches)return;
de.classList.add('m-js');
function fmt(n,plain){return plain?String(n):String(n).replace(/\\B(?=(\\d{3})+(?!\\d))/g,' ');}
function count(el){
  var t=+el.getAttribute('data-count');if(!isFinite(t))return;
  var suf=el.getAttribute('data-count-suffix')||'',plain=el.hasAttribute('data-count-plain'),s=null;
  function step(now){if(s===null)s=now;var p=Math.min(1,(now-s)/1200),e=1-Math.pow(1-p,4);
    el.textContent=fmt(Math.round(t*e),plain)+suf;if(p<1)requestAnimationFrame(step);}
  requestAnimationFrame(step);
}
/* React sahifani qayta chizsa (masalan, brauzer kengaytmasi yoki tarjimon
   HTML'ni o'zgartirib, hidratsiya qayta boshlansa) elementlar almashadi —
   eski tugunlarni kuzatib qolmaslik uchun hammasi joyida qayta qidiriladi
   (egasi 22.10: dadasining kompyuterida bo'limlar bo'sh qolgan edi). */
function init(){
  var seen=typeof WeakSet==='function'?new WeakSet():null;
  function show(el){if(el.classList.contains('m-in'))return;el.classList.add('m-in');
    if(el.hasAttribute('data-count'))count(el);
    el.querySelectorAll('[data-count]').forEach(count);}
  var io=new IntersectionObserver(function(es){es.forEach(function(e){
    if(!e.isIntersecting)return;show(e.target);io.unobserve(e.target);
  });},{rootMargin:'0px 0px -12% 0px',threshold:0});
  function watch(){d.querySelectorAll('[data-m]:not(.m-in)').forEach(function(el){
    if(seen){if(seen.has(el))return;seen.add(el);}io.observe(el);});}
  /* Zaxira: ekranga kirgan (yoki o'tib ketilgan) har qanday ochilmagan blok ochiladi. */
  function sweep(){d.querySelectorAll('[data-m]:not(.m-in)').forEach(function(el){
    var r=el.getBoundingClientRect();if(r.top<innerHeight*0.92&&(r.width||r.height))show(el);});}
  watch();
  var wq=0;
  if(window.MutationObserver)new MutationObserver(function(){if(wq)return;wq=requestAnimationFrame(function(){wq=0;watch();});})
    .observe(d.body||de,{childList:true,subtree:true});
  setTimeout(sweep,2500);
  var sq=0;
  function tick(){
    var h=d.querySelector('[data-site-header]');if(h)h.toggleAttribute('data-scrolled',scrollY>8);
    var bar=d.querySelector('.action-bar');
    if(bar){var f=d.getElementById('ariza'),on=false;
      if(f){var fr=f.getBoundingClientRect();on=fr.top<innerHeight*0.85&&fr.bottom>innerHeight*0.15;}
      bar.classList.toggle('is-shown',scrollY>innerHeight*0.55&&!on);}
  }
  tick();
  addEventListener('scroll',function(){tick();if(sq)return;sq=setTimeout(function(){sq=0;sweep();},250);},{passive:true});
  if(matchMedia('(pointer: fine)').matches){
    var raf=0,x=0,y=0,cur=null;
    d.addEventListener('pointermove',function(e){
      var el=e.target.closest&&e.target.closest('[data-spot]');
      if(cur&&cur!==el)cur.classList.remove('is-spot');cur=el;if(!el)return;
      var r=el.getBoundingClientRect();x=e.clientX-r.left;y=e.clientY-r.top;
      if(!raf)raf=requestAnimationFrame(function(){raf=0;if(!cur)return;cur.style.setProperty('--mx',x+'px');cur.style.setProperty('--my',y+'px');cur.classList.add('is-spot');});
    },{passive:true});
  }
}
if(d.readyState==='loading')d.addEventListener('DOMContentLoaded',init);else init();
}catch(e){document.documentElement.classList.remove('m-js');}})();`;
