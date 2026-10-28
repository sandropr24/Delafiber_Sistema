document.addEventListener("DOMContentLoaded", function() {
    let activeTab = localStorage.getItem("activeCatalogoTab");
    if (activeTab) {
        let tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (tabTrigger) {
            new bootstrap.Tab(tabTrigger).show();
        }
    }

    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(element) {
        element.addEventListener("show.bs.tab", function(event) {
            localStorage.setItem("activeCatalogoTab", event.target.getAttribute("data-bs-target"));
        });
    });
});

function resetSelectPadre() {
    const sel = document.getElementById('edit_idcategoriapadre');
    sel.disabled = false;
    sel.value = '';
    [...sel.options].forEach(o => o.disabled = false);
    document.getElementById('ayudaPadre').textContent = '';
    return sel;
}

function limpiarModalCategoria() {
    document.getElementById('modalCategoriaTitulo').textContent = 'Nueva Categoría';
    document.getElementById('edit_idcategoria').value = '';
    document.getElementById('edit_nombre_categoria').value = '';
    resetSelectPadre();
}

function nuevaSubcategoria(idPadre) {
    limpiarModalCategoria();
    document.getElementById('modalCategoriaTitulo').textContent = 'Nueva Subcategoría';
    document.getElementById('edit_idcategoriapadre').value = idPadre;
    new bootstrap.Modal(document.getElementById('modalNuevaCategoria')).show();
}

function editarCategoria(cat) {
    document.getElementById('modalCategoriaTitulo').textContent = 'Editar Categoría';
    document.getElementById('edit_idcategoria').value = cat.idcategoria;
    document.getElementById('edit_nombre_categoria').value = cat.categoria || '';

    const sel = resetSelectPadre();
    [...sel.options].forEach(o => o.disabled = (o.value == cat.idcategoria));
    sel.value = cat.idcategoriapadre || '';

    if (Number(cat.total_hijos) > 0) {
        sel.disabled = true;
        document.getElementById('ayudaPadre').textContent = 'Tiene subcategorías, por eso debe seguir siendo principal.';
    }

    new bootstrap.Modal(document.getElementById('modalNuevaCategoria')).show();
}

function limpiarModalMarca() {
    document.getElementById('modalMarcaTitulo').textContent = 'Nueva Marca';
    document.getElementById('edit_idmarca').value = '';
    document.getElementById('edit_nombre_marca').value = '';
}

function editarMarca(mar) {
    document.getElementById('modalMarcaTitulo').textContent = 'Editar Marca';
    document.getElementById('edit_idmarca').value = mar.idmarca;
    document.getElementById('edit_nombre_marca').value = mar.marca || '';

    new bootstrap.Modal(document.getElementById('modalNuevaMarca')).show();
}