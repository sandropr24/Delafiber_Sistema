document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("modalNotaVentaPdf");
  const iframe = document.getElementById("iframeNotaVentaPdf");
  const descargar = document.getElementById("descargarNotaVentaPdf");

  if (!modal || !iframe || !descargar) return;

  modal.addEventListener("show.bs.modal", (event) => {
    const boton = event.relatedTarget;
    if (!boton || !boton.classList.contains("js-nota-pdf")) return;
    const url = boton.getAttribute("href");
    if (!url) return;
    iframe.src = url;
    descargar.href = url;
    const id = url.split("/").filter(Boolean).pop() || "venta";
    descargar.setAttribute("download", `nota_venta_${id}.pdf`);
  });

  modal.addEventListener("hidden.bs.modal", () => {
    iframe.removeAttribute("src");
    descargar.href = "#";
    descargar.removeAttribute("download");
  });
});
