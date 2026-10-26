/**
 * Saytning 3D yadrosi — bosh qism sahnasi va 3D vitrina (bitta fayl, three.js ichida).
 *
 * Yig'ish:  npm run hamkor3d  → public/vendor/hamkor3d-<V>.js (~150 KB gzip)
 * O'zgarsa fayl nomidagi raqamni ham, lib/vitrina.ts > H3D_SRC ni ham oshiring
 * (hosting statik fayllarni 30 kun keshlaydi).
 *
 * TEZLIK QOIDALARI (egasi 26.10: "scroll payti qotishlarga sabab bo'lyapti"):
 *  - Muhit yorug'ligi (PMREM / environment map) YO'Q: Windows'da uning shaderlari
 *    1,2–1,5 s asosiy oqimni to'xtatadi (model-viewer shu sabab 2 s qotirgan edi).
 *    O'rniga uchta oddiy chiroq; metallar biroz xira qilinadi, aks holda qora ko'rinadi.
 *  - Har yangi model `compileAsync` dan keyin ko'rsatiladi — kompilyatsiya parallel.
 *  - Hammasi sahifa ochilib, brauzer bo'shaganda tayyorlanadi (lib/motion.ts), skroll
 *    paytida emas. Ekrandan chiqsa yoki sahifa yashirilsa chizish to'xtaydi.
 *  - Barmoq bilan aylantirish faqat gorizontal: telefonda sahifani tik surish bloklanmaydi.
 *
 * TELEFON (egasi 26.10: "telefonda sahifalar yuklanishga qiynalyapti"):
 *  - Modellar meshopt bilan siqilgan (`<id>-m.glb`, 5,2 → 2,4 MB). Yangi model:
 *    npx @gltf-transform/cli meshopt kirish.glb public/3d/<id>-m.glb
 *  - Bittadan yuklanadi, past ustuvorlikda, faqat o'z bloki ekranga yaqinlashganda.
 *    Avval hammasi birdan (1,2 MB) yuklanib, LTE'da keyingi sahifani navbatda ushlab turardi.
 *  - Sayt ichida boshqa sahifaga o'tilsa, yuklanayotgan model darhol to'xtatiladi.
 */
import {
  WebGLRenderer,
  Scene,
  PerspectiveCamera,
  Group,
  Box3,
  Vector3,
  ACESFilmicToneMapping,
  SRGBColorSpace,
  HemisphereLight,
  DirectionalLight,
  MathUtils,
  Mesh,
  PlaneGeometry,
  MeshBasicMaterial,
  CanvasTexture,
} from "three";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";
import { MeshoptDecoder } from "three/examples/jsm/libs/meshopt_decoder.module.js";

const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
const loader = new GLTFLoader().setMeshoptDecoder(MeshoptDecoder);
const cache = new Map();
const stop = new AbortController();
document.addEventListener(
  "click",
  (e) => {
    const a = e.target.closest?.("a[href]");
    if (a && a.origin === location.origin && a.pathname !== location.pathname) stop.abort();
  },
  true,
);

/** Modelni bir marta yuklaydi; har chaqiruvga alohida nusxa (bir model ikki sahnada bo'lishi mumkin). */
function loadModel(id) {
  if (!cache.has(id)) {
    cache.set(
      id,
      fetch(`/3d/${id}-m.glb`, { priority: "low", signal: stop.signal })
        .then((r) => (r.ok ? r.arrayBuffer() : Promise.reject(r.status)))
        .then((buf) => new Promise((ok, no) => loader.parse(buf, "/3d/", (g) => ok(g.scene), no))),
    );
  }
  return cache.get(id).then((s) => normalize(s.clone(true), FACE[id] || 0));
}

/* Muallif old tomonini kameraga (+z) qaratmagan modellar — shuncha radian buriladi. */
const FACE = { "kir-mashina": -1.0 };

/** Blok ekranga `margin` qadar yaqinlashganda bir marta `fn` ni chaqiradi. */
function whenNear(el, margin, fn) {
  const io = new IntersectionObserver((es) => {
    if (!es.some((e) => e.isIntersecting)) return;
    io.disconnect();
    fn();
  }, { rootMargin: margin });
  io.observe(el);
}

/** Markazga olib, eng katta o'lchamini 1 ga keltiradi (mualliflarning mm/sm farqi yo'qoladi). */
function normalize(root, face) {
  root.traverse((o) => {
    if (!o.isMesh) return;
    for (const m of Array.isArray(o.material) ? o.material : [o.material]) {
      // Muhit aksi yo'q — to'liq metall qop-qora ko'rinardi; cho'tkalangan metalldek qilamiz.
      if ("metalness" in m) {
        m.metalness = Math.min(m.metalness, 0.35);
        m.roughness = Math.max(m.roughness, 0.38);
      }
    }
  });
  root.rotation.y = face;
  root.updateMatrixWorld(true);
  const box = new Box3().setFromObject(root);
  const size = box.getSize(new Vector3());
  root.position.sub(box.getCenter(new Vector3()));
  const max = Math.max(size.x, size.y, size.z) || 1;
  const spin = new Group();
  spin.add(root);
  spin.scale.setScalar(1 / max);
  const outer = new Group();
  outer.add(spin);
  outer.userData.size = new Vector3(size.x / max, size.y / max, size.z / max);
  return outer;
}

function makeRenderer(maxDpr) {
  const r = new WebGLRenderer({ antialias: true, alpha: true, powerPreference: "low-power" });
  r.setPixelRatio(Math.min(window.devicePixelRatio || 1, maxDpr));
  r.outputColorSpace = SRGBColorSpace;
  r.toneMapping = ACESFilmicToneMapping;
  r.toneMappingExposure = 1.1;
  r.domElement.setAttribute("aria-hidden", "true");
  return r;
}

/** Brend kayfiyatidagi yorug'lik: tepadan iliq binafsha, old-chapdan iliq asosiy nur, orqadan kontur. */
function addLights(scene) {
  scene.add(new HemisphereLight(0xe6dbff, 0x2a1543, 1.6));
  const key = new DirectionalLight(0xfff3d6, 2.2);
  key.position.set(-4, 6, 8);
  scene.add(key);
  const fill = new DirectionalLight(0xffffff, 0.8);
  fill.position.set(5, 1, 6);
  scene.add(fill);
  const rim = new DirectionalLight(0xb28cff, 2.4);
  rim.position.set(3, 3, -8);
  scene.add(rim);
}

/** Faqat ko'rinib turganda chizadi. `frame(now)` true qaytarsa — keyingi kadr ham kerak. */
function loop(host, frame) {
  let running = false;
  let visible = false;
  const tick = (now) => {
    if (!running) return;
    if (frame(now) === false) {
      running = false;
      return;
    }
    requestAnimationFrame(tick);
  };
  const play = () => {
    if (running || !visible || document.hidden) return;
    running = true;
    requestAnimationFrame(tick);
  };
  new IntersectionObserver((es) => {
    visible = es[0].isIntersecting;
    if (visible) play();
    else running = false;
  }).observe(host);
  document.addEventListener("visibilitychange", () => (document.hidden ? (running = false) : play()));
  return play;
}

// ============================================================ bosh qism

/* gap — matn ustuni va forma orasidagi oraliqda (sahifadan o'lchanadi); nx/ny — ekranda
   (-1…1); z — chuqurlik; s — eng katta tomoni ekran balandligiga nisbatan. */
const BG = [
  { id: "uzuk", gap: true, ny: 0.5, z: 1.5, s: 0.1, spin: 0.6 },
  { id: "televizor", gap: true, ny: 0.15, z: 0.5, s: 0.11, spin: -0.22 },
  { id: "mikroto-lqin", gap: true, ny: -0.2, z: 1, s: 0.11, spin: 0.3 },
  { id: "kir-mashina", gap: true, ny: -0.52, z: 0.5, s: 0.11, spin: -0.26 },
  { id: "muzlatgich", nx: 1.0, ny: -0.2, z: -4, s: 0.5, spin: 0.18 },
];
const BAND = ["muzlatgich", "televizor", "uzuk", "kir-mashina", "mikroto-lqin"];
/* 404: mahsulotlar katta "404" raqami atrofida "adashib" suzadi (raqam CSS da, markazda). */
const LOST = [
  // m — telefon (tik ekran): matn o'rtada, mahsulotlar tepada va pastki burchaklarda.
  { id: "televizor", nx: -0.62, ny: 0.42, z: -1, s: 0.2, spin: 0.25, m: { nx: -0.5, ny: 0.66, s: 0.2 } },
  { id: "uzuk", nx: 0.58, ny: 0.5, z: 1, s: 0.13, spin: -0.5, m: { nx: 0.55, ny: 0.7, s: 0.14 } },
  { id: "kir-mashina", nx: 0.7, ny: -0.42, z: -0.5, s: 0.2, spin: -0.2, m: { nx: 0.72, ny: -0.86, s: 0.13 } },
  { id: "mikroto-lqin", nx: -0.66, ny: -0.46, z: 0.5, s: 0.16, spin: 0.3, m: { nx: -0.72, ny: -0.86, s: 0.13 } },
  { id: "divan", nx: 0.02, ny: -0.93, z: -2, s: 0.22, spin: 0.12, m: { nx: 0.02, ny: 0.47, s: 0.15 } },
];
const FOV = 30;
const CAM_Z = 14;
const visibleHeight = (depth) => 2 * Math.tan(MathUtils.degToRad(FOV / 2)) * (CAM_Z - depth);

export function mountHero(host, mode) {
  const renderer = makeRenderer(mode === "bg" ? 1.5 : 1.5);
  host.appendChild(renderer.domElement);
  const scene = new Scene();
  addLights(scene);
  const camera = new PerspectiveCamera(FOV, 1, 0.1, 100);
  camera.position.set(0, mode === "band" ? 1.6 : 0, CAM_Z);
  camera.lookAt(0, 0, 0);
  const world = new Group();
  scene.add(world);
  const items = [];

  function measureGap() {
    const form = host.closest("#hero")?.querySelector(".hero-lead-wrap");
    const text = form?.previousElementSibling;
    if (!form || !text) return { c: 0.09, w: 0.07 };
    const hr = host.getBoundingClientRect();
    // Ustun qutisi emas, matn qatorlari va tugmalarning haqiqiy o'ng cheti.
    let right = 0;
    const range = document.createRange();
    const walk = document.createTreeWalker(text, NodeFilter.SHOW_TEXT);
    for (let n = walk.nextNode(); n; n = walk.nextNode()) {
      if (!n.textContent.trim()) continue;
      range.selectNodeContents(n);
      for (const rc of range.getClientRects()) right = Math.max(right, rc.right);
    }
    text.querySelectorAll("a, .hero-pill").forEach((el) => (right = Math.max(right, el.getBoundingClientRect().right)));
    const a = (right || text.getBoundingClientRect().right) - hr.left;
    const b = form.getBoundingClientRect().left - hr.left;
    return { c: ((a + b) / 2 / hr.width) * 2 - 1, w: Math.max(0.02, (b - a) / hr.width) };
  }

  function layout() {
    const w = host.clientWidth || 1;
    const h = host.clientHeight || 1;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    if (mode === "bg" || mode === "lost") {
      const gap = mode === "bg" ? measureGap() : { c: 0, w: 1 };
      for (const it of items) {
        const c = mode === "lost" && camera.aspect < 0.8 && it.cfg.m ? { ...it.cfg, ...it.cfg.m } : it.cfg;
        const vh = visibleHeight(c.z);
        const vw = vh * camera.aspect;
        const nx = c.gap ? gap.c : c.nx;
        it.base.set((nx * vw) / 2, (c.ny * vh) / 2, c.z);
        // Oraliqdagilar oraliqdan tor: aylanayotgan quti burilganda enliroq ko'rinadi.
        // 404 da tor (telefon) ekranda kenglikka qarab kichrayadi — matn ustiga chiqmasin.
        const base = mode === "lost" ? Math.min(vh, vw * 1.1) : vh;
        it.size = c.gap ? Math.min(c.s * vh, gap.w * 0.75 * vw) : c.s * base;
      }
    } else {
      const vh = visibleHeight(0);
      const fit = Math.min(vh * 0.78, vh * camera.aspect * 0.62);
      for (const it of items) {
        it.base.set(0, 0, 0);
        it.size = fit;
      }
    }
  }

  const list = mode === "bg" ? BG : mode === "lost" ? LOST : BAND.map((id) => ({ id, spin: 0.35 }));
  // Navbat bilan: keyingisi oldingisi ko'rsatilgach (tarmoq ham, shader kompilyatsiyasi ham bo'linadi).
  async function next(i) {
    if (i >= list.length) return;
    const cfg = list[i];
    const node = await loadModel(cfg.id).catch(() => null);
    if (!node) return;
    node.visible = false;
    world.add(node);
    await renderer.compileAsync(scene, camera);
    const it = { cfg, node, base: new Vector3(), size: 1, born: performance.now() + 140, phase: i * 1.7 };
    items.push(it);
    node.visible = true;
    layout();
    host.classList.add("is-on");
    play();
    next(i + 1);
  }
  whenNear(host, "200px", () => next(0));

  let mx = 0;
  let my = 0;
  let tx = 0;
  let ty = 0;
  if (mode !== "band" && matchMedia("(pointer: fine)").matches) {
    window.addEventListener(
      "pointermove",
      (e) => {
        tx = (e.clientX / window.innerWidth) * 2 - 1;
        ty = (e.clientY / window.innerHeight) * 2 - 1;
      },
      { passive: true },
    );
  }

  const start = performance.now();
  const SLOT = 3.2;
  const play = loop(host, (now) => {
    const t = (now - start) / 1000;
    mx += (tx - mx) * 0.05;
    my += (ty - my) * 0.05;
    const rect = host.getBoundingClientRect();
    const out = mode === "bg" ? MathUtils.clamp(-rect.top / Math.max(rect.height, 1), 0, 1) : 0;
    if (mode !== "band") {
      world.rotation.y = mx * (mode === "lost" ? 0.2 : 0.12);
      world.rotation.x = my * 0.06;
    }
    const order = mode === "band" ? items.slice().sort((a, b) => BAND.indexOf(a.cfg.id) - BAND.indexOf(b.cfg.id)) : items;
    const cur = order.length ? Math.floor(t / SLOT) % order.length : 0;
    const inSlot = (t % SLOT) / SLOT;
    for (const it of items) {
      const age = Math.min(1, Math.max(0, (now - it.born) / 900));
      let pop = age >= 1 ? 1 : 1 + 1.6 * Math.pow(age - 1, 3) + 0.6 * Math.pow(age - 1, 2);
      if (mode === "band") {
        const on = order[cur] === it;
        const k = !on ? 0 : inSlot < 0.14 ? inSlot / 0.14 : inSlot > 0.86 ? (1 - inSlot) / 0.14 : 1;
        pop = k <= 0 ? 0 : 1 - Math.pow(1 - k, 3);
      }
      const bob = reduce ? 0 : Math.sin(t * 0.9 + it.phase) * 0.12;
      it.node.position.set(it.base.x * (1 + out * 0.35), it.base.y + bob + out * 2.2, it.base.z);
      const spin = it.node.children[0];
      // Telefonda mahsulot yuzi bilan turadi: o'ngdan chapga ±23° buriladi. ±43° da kir mashina
      // navbati boshida yon tomoni bilan "oq quti" bo'lib ko'rinardi (egasi 26.10 skrinshoti).
      spin.rotation.y = reduce ? 0.35 : mode === "band" ? 0.4 - inSlot * 0.8 : t * (it.cfg.spin || 0.3) + it.phase;
      spin.rotation.x = mode !== "band" && !reduce ? Math.sin(t * 0.5 + it.phase) * (mode === "lost" ? 0.25 : 0.08) : 0;
      it.node.scale.setScalar(it.size * Math.max(0.0001, pop));
      it.node.visible = pop > 0.001;
    }
    renderer.render(scene, camera);
    return !reduce || items.some((it) => now - it.born < 1000);
  });
  new ResizeObserver(layout).observe(host);
  layout();
}

// ============================================================ 3D vitrina

/** Pol soyasi: radial gradient tekstura (hisoblanadigan soya o'rniga — arzon). */
function shadowPlane() {
  const c = document.createElement("canvas");
  c.width = c.height = 128;
  const g = c.getContext("2d");
  const grd = g.createRadialGradient(64, 64, 4, 64, 64, 64);
  grd.addColorStop(0, "rgba(8,2,18,0.55)");
  grd.addColorStop(1, "rgba(8,2,18,0)");
  g.fillStyle = grd;
  g.fillRect(0, 0, 128, 128);
  const m = new Mesh(new PlaneGeometry(1, 1), new MeshBasicMaterial({ map: new CanvasTexture(c), transparent: true, depthWrite: false }));
  m.rotation.x = -Math.PI / 2;
  return m;
}

export function mountVitrina(root) {
  const stage = root.querySelector(".vt-stage");
  if (!stage) return;
  const renderer = makeRenderer(1.75);
  renderer.domElement.className = "vt-canvas";
  stage.appendChild(renderer.domElement);
  const scene = new Scene();
  addLights(scene);
  const camera = new PerspectiveCamera(28, 1, 0.1, 50);
  camera.position.set(0, 0.55, 4.2);
  camera.lookAt(0, -0.05, 0);
  const shadow = shadowPlane();
  scene.add(shadow);

  let current = null; // { id, node }
  let wanted = null;
  let yaw = 0; // foydalanuvchi burgani (surish bilan)
  let vel = 0;
  let dragging = false;
  let fade = 1;

  function size() {
    const w = stage.clientWidth || 1;
    const h = stage.clientHeight || 1;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    // Tor ekranda (telefon) kamerani uzoqlashtiramiz — model kesilmasin.
    camera.position.z = camera.aspect < 1 ? 4.2 / camera.aspect ** 0.6 : 4.2;
    camera.updateProjectionMatrix();
    play();
  }

  function selectedId() {
    const r = root.querySelector(".vt-r:checked");
    return r ? r.getAttribute("data-id") : null;
  }

  async function show(id) {
    wanted = id;
    if (current && current.id === id) return;
    root.classList.remove("is-3d"); // yuklanguncha shu mahsulotning rasmi ko'rinib turadi
    const node = await loadModel(id).catch(() => null);
    if (!node || wanted !== id) return;
    const s = node.userData.size;
    // Eng baland/ken tomoni sahnaga sig'sin; pastini polga qo'yamiz.
    const k = 1.55 / Math.max(s.y * 1.25, s.x, s.z);
    node.scale.setScalar(k);
    node.position.y = -0.75 + (s.y * k) / 2;
    shadow.scale.set(Math.max(s.x, s.z) * k * 1.6, Math.max(s.x, s.z) * k * 1.6, 1);
    shadow.position.y = -0.749;
    node.children[0].userData.s0 = node.children[0].scale.x;
    node.visible = false;
    scene.add(node);
    await renderer.compileAsync(scene, camera);
    if (wanted !== id) {
      scene.remove(node);
      return;
    }
    if (current) scene.remove(current.node);
    node.visible = true;
    current = { id, node };
    fade = 0;
    root.classList.add("is-3d");
    play();
  }

  // Gorizontal surish — aylantirish; tik surish sahifani suradi (touch-action: pan-y CSS da).
  let lastX = 0;
  let lastT = 0;
  stage.addEventListener("pointerdown", (e) => {
    dragging = true;
    lastX = e.clientX;
    lastT = performance.now();
    vel = 0;
    stage.setPointerCapture?.(e.pointerId);
    play();
  });
  stage.addEventListener("pointermove", (e) => {
    if (!dragging) return;
    const dx = e.clientX - lastX;
    const now = performance.now();
    yaw += dx * 0.012;
    vel = (dx * 0.012) / Math.max(1, now - lastT) * 16;
    lastX = e.clientX;
    lastT = now;
  });
  const up = () => (dragging = false);
  stage.addEventListener("pointerup", up);
  stage.addEventListener("pointercancel", up);
  // Klaviatura: tanlangan mahsulotni strelka bilan almashtirish radio'da; bu yerda faqat o'zgarishni tinglaymiz.
  root.addEventListener("change", (e) => {
    if (e.target.classList?.contains("vt-r")) show(e.target.getAttribute("data-id"));
  });

  let prev = performance.now();
  const t0 = prev;
  const play = loop(stage, (now) => {
    const dt = Math.min(0.05, (now - prev) / 1000);
    prev = now;
    if (!dragging) {
      vel *= 0.94;
      yaw += vel;
    }
    // O'z-o'zidan: old tomon atrofida yumshoq chayqalish (±40°) — orqa paneli emas, yuzi ko'rinadi.
    const swing = reduce ? 0.45 : 0.45 + Math.sin((now - t0) / 1000 * 0.5) * 0.7;
    if (current) {
      // Yangi mahsulot biroz kichikdan o'z o'lchamiga "ochiladi".
      fade = Math.min(1, fade + dt * 3.5);
      const e = 1 - Math.pow(1 - fade, 3);
      const spin = current.node.children[0];
      spin.rotation.y = yaw + swing;
      spin.scale.setScalar(spin.userData.s0 * (0.9 + 0.1 * e));
    }
    renderer.render(scene, camera);
    return dragging || Math.abs(vel) > 0.0005 || !reduce || fade < 1;
  });
  new ResizeObserver(size).observe(stage);
  size();
  const first = selectedId();
  if (first) show(first);
}

/** Vitrina sahifa ochilganda emas, foydalanuvchi unga yaqinlashganda tayyorlanadi. */
function lazyVitrina(root) {
  const stage = root.querySelector(".vt-stage");
  if (stage) whenNear(stage, "600px 0px", () => mountVitrina(root));
}

// ============================================================ ishga tushirish

const heroOk = window.__h3dHero !== false;
if (heroOk) {
  const hosts = [...document.querySelectorAll("[data-hero3d]")];
  const shown = hosts.find((h) => (h.checkVisibility ? h.checkVisibility() : h.offsetParent !== null));
  if (shown) {
    const m = shown.getAttribute("data-hero3d");
    mountHero(shown, m === "band" || m === "lost" ? m : "bg");
  }
}
document.querySelectorAll("[data-3d]").forEach(lazyVitrina);
