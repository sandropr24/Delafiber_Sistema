<div class="modal fade" id="modalClienteExpress" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus text-primary"></i>
                    Registrar Cliente Rápido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formClienteExpress" data-url-express="<?= base_url('admin/clientes/guardar-express') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="tipoDocClienteExpress" class="form-label small fw-bold text-secondary text-uppercase">Tipo de documento</label>
                        <select id="tipoDocClienteExpress" name="tipodoc" class="form-select" required>
                            <option value="DNI">DNI</option>
                            <option value="RUC">RUC</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">
                            Documento<span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            name="documento"
                            value="<?= old('documento') ?>"
                            class="form-control"
                            inputmode="numeric"
                            minlength="8"
                            maxlength="8"
                            pattern="[0-9]{8}"
                            title="Ingrese exactamente 8 dígitos para DNI."
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Nombre o Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" minlength="2" maxlength="100" pattern="[^0-9]+" title="El nombre no debe contener números." required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Teléfono / Celular</label>
                        <input type="text" name="telefono" class="form-control" inputmode="numeric" pattern="[0-9]{1,9}" maxlength="9" title="Ingrese solo números, hasta 9 dígitos.">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-sm btn-light border text-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm" id="btnGuardarClienteExpress">
                        <i class="bi bi-save me-1"></i> Guardar y Seleccionar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
