document.addEventListener("DOMContentLoaded", function () {
  let activeTab = localStorage.getItem("activeCatalogoTab");
  if (activeTab) {
    let tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
    if (tabTrigger) {
      let tab = new bootstrap.Tab(tabTrigger);
      tab.show();
    }
  }

  document
    .querySelectorAll('button[data-bs-togle="tab"]')
    .forEach(function (element) {
      element.addEventListener("show.bs.tab", function (event) {
        localStorage.setItem(
          "activeCatalogoTab",
          event.target.getAttribute("data-bs-target"),
        );
      });
    });

  document.querySelectorAll(".btn-editar-categoria").forEach((button) => {
    button.addEventListener("click", function () {
      let id = this.getAttribute("data-id");
      let nombre = this.getAttribute("data-nombre");

      document.getElementById("edit_idcategoria").value = id;
      document.getElementById("edit_nombre_categoria").value = nombre;

      let modal = new bootstrap.Modal(
        document.getElementById("modalEditarCategoria"),
      );
      modal.show();
    });
  });

  document.querySelectorAll(".btn-editar-marca").forEach((button) => {
    button.addEventListener("click", function () {
      let id = this.getAttribute("data-id");
      let nombre = this.getAttribute("data-nombre");

      document.getElementById("edit_idmarca").value = id;
      document.getElementById("edit_nombre_marca").value = nombre;

      let modal = new bootstrap.Modal(
        document.getElementById("modalEditarMarca"),
      );
      modal.show();
    });
  });

  document.querySelectorAll(".btn-eliminar-categoria").forEach((button) => {
    button.addEventListener("click", function () {
      let nombre = this.getAttribute("data-nombre");
      let url = this.getAttribute("data-url");

      Swal.fire({
        title: "¿Estás seguro?",
        text: `Se eliminará la categoría "${nombre}"`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = url;
        }
      });
    });
  });

  document.querySelectorAll(".btn-eliminar-marca").forEach((button) => {
    button.addEventListener("click", function () {
      let nombre = this.getAttribute("data-nombre");
      let url = this.getAttribute("data-url");

      Swal.fire({
        title: "¿Estas seguro",
        text: `Se eliminará la marca "${nombre}"`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = url;
        }
      });
    });
  });
});
