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
 *  - `[data-spot]` — sichqoncha bilan: kursor o'rni `--mx`/`--my` ga
 *    yoziladi (sahnadagi yorug'lik). Barmoq bilan ishlatilmaydi.
 *
 * Xavfsizlik: yashirish `.m-js` sinfiga bog'langan va u faqat shu skript
 * ishlaganda qo'yiladi. Kamaytirilgan harakat sozlamasida qo'yilmaydi.
 * Biror sabab bilan kuzatuvchi ishlamasa, 4 soniyadan keyin hammasi
 * baribir ko'rinadi.
 */
export const MOTION_SCRIPT = `(function(){try{
var d=document,de=d.documentElement;
if(!('IntersectionObserver' in window))return;
if(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches)return;
de.classList.add('m-js');
function fmt(n,plain){return plain?String(n):String(n).replace(/\B(?=(\d{3})+(?!\d))/g,' ');}
function count(el){
  var t=+el.getAttribute('data-count');if(!isFinite(t))return;
  var suf=el.getAttribute('data-count-suffix')||'',plain=el.hasAttribute('data-count-plain'),s=null;
  function step(now){if(s===null)s=now;var p=Math.min(1,(now-s)/1200),e=1-Math.pow(1-p,4);
    el.textContent=fmt(Math.round(t*e),plain)+suf;if(p<1)requestAnimationFrame(step);}
  requestAnimationFrame(step);
}
function init(){
  var els=d.querySelectorAll('[data-m]');
  var io=new IntersectionObserver(function(es){es.forEach(function(e){
    if(!e.isIntersecting)return;var el=e.target;el.classList.add('m-in');
    if(el.hasAttribute('data-count'))count(el);
    el.querySelectorAll('[data-count]').forEach(count);
    io.unobserve(el);
  });},{rootMargin:'0px 0px -6% 0px',threshold:0.12});
  els.forEach(function(el){io.observe(el);});
  setTimeout(function(){d.querySelectorAll('[data-m]:not(.m-in)').forEach(function(el){
    var r=el.getBoundingClientRect();if(r.top<innerHeight)el.classList.add('m-in');});},4000);
  var h=d.querySelector('[data-site-header]'),on=null;
  function sc(){var v=scrollY>8;if(v===on)return;on=v;if(h)h.toggleAttribute('data-scrolled',v);}
  sc();addEventListener('scroll',sc,{passive:true});
  var bar=d.querySelector('.action-bar'),form=d.getElementById('ariza'),formOn=false;
  if(bar){
    var upd=function(){bar.classList.toggle('is-shown',scrollY>innerHeight*0.55&&!formOn);};
    if(form)new IntersectionObserver(function(es){formOn=es[0].isIntersecting;upd();},{threshold:0.15}).observe(form);
    upd();addEventListener('scroll',upd,{passive:true});
  }
  if(matchMedia('(pointer: fine)').matches)d.querySelectorAll('[data-spot]').forEach(function(el){
    var raf=0,x=0,y=0;
    el.addEventListener('pointermove',function(e){var r=el.getBoundingClientRect();x=e.clientX-r.left;y=e.clientY-r.top;
      if(!raf)raf=requestAnimationFrame(function(){raf=0;el.style.setProperty('--mx',x+'px');el.style.setProperty('--my',y+'px');el.classList.add('is-spot');});});
    el.addEventListener('pointerleave',function(){el.classList.remove('is-spot');});
  });
}
if(d.readyState==='loading')d.addEventListener('DOMContentLoaded',init);else init();
}catch(e){document.documentElement.classList.remove('m-js');}})();`;
