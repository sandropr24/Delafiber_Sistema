function limpiarFormulario() {
  document.getElementById("modalTitulo").textContent = "Nuevo Usuario";
  document.getElementById("idusuario").value = "";
  document.getElementById("idpersona").value = "";
  document.getElementById("nombres").value = "";
  document.getElementById("apellidos").value = "";
  document.getElementById("tipodoc").value = "DNI";
  document.getElementById("numerodoc").value = "";
  document.getElementById("telefono").value = "";
  document.getElementById("email").value = "";
  document.getElementById("direccion").value = "";
  document.getElementById("nombreusuario").value = "";
  document.getElementById("idrol").value = "";
  document.getElementById("claveacceso").value = "";

  document.getElementById("claveacceso").required = true;
  document.getElementById("helpClave").style.display = "none";
}

function editarUsuario(u) {
  document.getElementById("modalTitulo").textContent = "Editar Usuario";
  document.getElementById("idusuario").value = u.idusuario;
  document.getElementById("idpersona").value = u.idpersona || "";
  document.getElementById("nombres").value = u.nombres || "";
  document.getElementById("apellidos").value = u.apellidos || "";
  document.getElementById("tipodoc").value = u.tipodoc || "DNI";
  document.getElementById("numerodoc").value = u.numerodoc || "";
  document.getElementById("telefono").value = u.telefono || "";
  document.getElementById("email").value = u.email || "";
  document.getElementById("direccion").value = u.direccion || "";
  document.getElementById("nombreusuario").value = u.nombreusuario;
  document.getElementById("idrol").value = u.idrol || "";
  document.getElementById("claveacceso").value = "";

  document.getElementById("claveacceso").required = false;
  document.getElementById("helpClave").style.display = "block";

  new bootstrap.Modal(document.getElementById("modalUsuario")).show();
}