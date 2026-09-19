/* Tanlangan mavzuni sahifa chizilishidan OLDIN qo'yadi — aks holda qorong'i
   rejimda har ochilishda bir lahza oq ekran yiltillaydi. Shuning uchun <head>
   ichida, defer'siz ulanadi. CSP inline skriptni taqiqlaydi, shu sabab alohida fayl. */
(function () {
  try {
    var t = localStorage.getItem("hs-theme");
    if (t === "dark" || t === "light") document.documentElement.setAttribute("data-theme", t);
  } catch (e) {}
})();
