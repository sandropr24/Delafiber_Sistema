<?= $this->extend('layouts/main') ?>

<?= $this->section('contenido') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Catálogo de Productos</h4>
        <p class="text-muted small mb-0">Gestión de insumos, materiales y equipos de telecomunicaciones.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto" onclick="limpiarFormulario()">
        <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
    </button>
</div>

<!-- Errores de validación -->
<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-secondary">
                    <th class="ps-4" style="width: 60px;">Foto</th>
                    <th>Descripción</th>
                    <th>Categoría</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Cód. Barras</th>
                    <th class="text-end">P. Venta</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end pe-4" style="width: 110px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No hay productos registrados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td class="ps-4">
                                <?php if (!empty($p['imagen']) && is_file(FCPATH . 'uploads/productos/' . $p['imagen'])): ?>
                                    <img src="<?= base_url('uploads/productos/' . esc($p['imagen'])) ?>" 
                                         alt="<?= esc($p['descripcion']) ?>" 
                                         class="rounded border object-fit-cover shadow-sm" 
                                         style="width: 44px; height: 44px;">
                                <?php else: ?>
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center text-secondary" 
                                         style="width: 44px; height: 44px;">
                                        <i class="bi bi-image fs-5"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold text-dark"><?= esc($p['descripcion']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= esc($p['categoria']) ?></span></td>
                            <td><?= esc($p['marca']) ?></td>
                            <td class="text-muted small"><?= esc($p['modelo'] ?: '-') ?></td>
                            <td class="text-muted small font-monospace"><?= esc($p['codigobarras'] ?: '-') ?></td>
                            <td class="text-end fw-bold text-dark">S/ <?= number_format($p['precioventa'], 2) ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('productos/cambiarestado/' . $p['idproducto']) ?>" 
                                   class="badge <?= $p['estado'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> text-decoration-none"
                                   title="Clic para cambiar estado">
                                    <?= $p['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                                </a>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editarProducto(<?= esc(json_encode($p)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('productos/guardar') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="idproducto" id="idproducto">

                <div class="modal-header py-3">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="descripcion" class="form-label small fw-semibold">Descripción del Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion" required placeholder="Ej. Cable Drop Fibra Óptica 1 Hilo 1000m">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="idcategoria" class="form-label small fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="idcategoria" name="idcategoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['idcategoria'] ?>"><?= esc($c['categoria']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="idmarca" class="form-label small fw-semibold">Marca <span class="text-danger">*</span></label>
                            <select class="form-select" id="idmarca" name="idmarca" required>
                                <option value="">Seleccione una marca</option>
                                <?php foreach ($marcas as $m): ?>
                                    <option value="<?= $m['idmarca'] ?>"><?= esc($m['marca']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="modelo" class="form-label small fw-semibold">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo" placeholder="Ej. GJYXFCH-1B">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="codigobarras" class="form-label small fw-semibold">Código de Barras</label>
                            <input type="text" class="form-control" id="codigobarras" name="codigobarras" placeholder="Ej. 775123456789">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="precioventa" class="form-label small fw-semibold">Precio Venta (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="precioventa" name="precioventa" required placeholder="0.00">
                        </div>

                        
                        <div class="col-12 col-md-8">
                            <label for="imagen" class="form-label small fw-semibold">Imagen del Producto (Opcional)</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/png, image/jpeg, image/webp" onchange="previsualizarImagen(event)">
                            <small class="text-muted" style="font-size: 0.75rem;">Admite JPG, PNG o WEBP. Máximo 2MB.</small>
                        </div>

                        <div class="col-12 col-md-4 text-center">
                            <label class="form-label small fw-semibold d-block">Vista previa</label>
                            <div class="d-flex align-items-center justify-content-center bg-light border rounded mx-auto" style="width: 70px; height: 70px; overflow: hidden;">
                                <img id="previewImg" src="" alt="Previsualización" class="w-100 h-100 object-fit-cover" style="display: none;">
                                <i id="previewIcon" class="bi bi-image fs-3 text-secondary"></i>
                            </div>
                        </div>

                        <div class="col-12" id="divEstado" style="display: none;">
                            <label for="estado" class="form-label small fw-semibold">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function previsualizarImagen(event) {
        const input = event.target;
        const img = document.getElementById('previewImg');
        const icon = document.getElementById('previewIcon');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                img.style.display = 'block';
                icon.style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function limpiarFormulario() {
        document.getElementById('modalTitulo').textContent = 'Nuevo Producto';
        document.getElementById('idproducto').value = '';
        document.getElementById('descripcion').value = '';
        document.getElementById('idcategoria').value = '';
        document.getElementById('idmarca').value = '';
        document.getElementById('modelo').value = '';
        document.getElementById('codigobarras').value = '';
        document.getElementById('precioventa').value = '';
        document.getElementById('imagen').value = '';
        document.getElementById('divEstado').style.display = 'none';
        document.getElementById('estado').value = '1';

        document.getElementById('previewImg').src = '';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('previewIcon').style.display = 'block';
    }

    function editarProducto(p) {
        document.getElementById('modalTitulo').textContent = 'Editar Producto';
        document.getElementById('idproducto').value = p.idproducto;
        document.getElementById('descripcion').value = p.descripcion;
        document.getElementById('idcategoria').value = p.idcategoria;
        document.getElementById('idmarca').value = p.idmarca;
        document.getElementById('modelo').value = p.modelo || '';
        document.getElementById('codigobarras').value = p.codigobarras || '';
        document.getElementById('precioventa').value = p.precioventa;
        document.getElementById('imagen').value = '';
        document.getElementById('divEstado').style.display = 'block';
        document.getElementById('estado').value = p.estado;

        const img = document.getElementById('previewImg');
        const icon = document.getElementById('previewIcon');
        if (p.imagen) {
            img.src = '<?= base_url('uploads/productos/') ?>/' + p.imagen;
            img.style.display = 'block';
            icon.style.display = 'none';
        } else {
            img.src = '';
            img.style.display = 'none';
            icon.style.display = 'block';
        }

        new bootstrap.Modal(document.getElementById('modalProducto')).show();
    }
</script>
<?= $this->endSection() ?>