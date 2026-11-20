<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<div class="container-fluid df-page">
    <div class="df-page-heading d-flex justify-content-end mb-3">
        <a class="btn btn-light border" href="<?= base_url('admin/caja') ?>">
            <i class="bi bi-arrow-left me-1"></i> Volver a caja y cobros
        </a>
    </div>
    <div class="alert alert-info">Registra las cuentas reales de la empresa. No se crean números de cuenta de demostración. Para conservar el historial, cajas y cuentas se desactivan, no se eliminan ni se sobrescriben.</div>
    <div class="card card-body mb-4">
        <h2 class="h5">Medios de pago</h2>
        <p><?= esc(implode(' · ', array_map(static fn($medio) => $medio['tipopago'] . (empty($medio['estado']) ? ' (inactivo)' : ''), $medios))) ?: 'Sin medios configurados.' ?></p>
        <form action="<?= base_url('admin/caja/completar-medios') ?>" method="post"><?= csrf_field() ?><button class="btn btn-outline-primary">Agregar medios faltantes (incluye QR y depósito)</button></form>
    </div>
    <div class="card card-body mb-4">
        <h2 class="h5">Puntos de caja</h2>
        <form class="row g-2 mb-3" action="<?= base_url('admin/caja/crear-caja') ?>" method="post">
            <?= csrf_field() ?>
            <div class="col-md-5"><label for="localCaja" class="form-label">Local</label><select id="localCaja" name="idlocal" class="form-select" required>
                    <option value="">Selecciona</option><?php foreach ($locales as $local): ?><option value="<?= (int) $local['idlocal'] ?>"><?= esc($local['nombrelocal']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-5"><label for="nombreCaja" class="form-label">Nombre de caja</label><input id="nombreCaja" name="nombre" class="form-control" minlength="2" maxlength="60" required placeholder="Caja principal"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Crear caja</button></div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Local</th>
                        <th>Caja</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cajas as $caja): ?><tr>
                            <td><?= esc($caja['nombrelocal']) ?></td>
                            <td><?= esc($caja['nombre']) ?></td>
                            <td><?= $caja['estado'] ? 'Activa' : 'Inactiva' ?></td>
                            <td>
                                <form method="post" action="<?= base_url('admin/caja/estado-caja/' . (int) $caja['idcaja']) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="<?= $caja['estado'] ? 0 : 1 ?>"><button class="btn btn-sm btn-outline-secondary"><?= $caja['estado'] ? 'Desactivar' : 'Activar' ?></button></form>
                            </td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card card-body">
        <h2 class="h5">Cuentas destino</h2>
        <p class="text-muted">Las ventas actuales se cobran en soles: solo podrán seleccionar cuentas PEN activas.</p>
        <form class="row g-2 mb-3" action="<?= base_url('admin/caja/crear-cuenta') ?>" method="post">
            <?= csrf_field() ?>
            <div class="col-md-2"><label for="banco" class="form-label">Banco</label><select id="banco" name="banco" class="form-select" required><?php foreach (['BCP', 'BBVA', 'Interbank'] as $banco): ?><option><?= esc($banco) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label for="cuenta" class="form-label">Número de cuenta</label><input id="cuenta" name="numerocuenta" class="form-control" pattern="[0-9][0-9\-]{4,28}[0-9]" maxlength="30" required></div>
            <div class="col-md-3"><label for="cci" class="form-label">CCI (opcional)</label><input id="cci" name="cci" class="form-control" pattern="[0-9]{20}" maxlength="20"></div>
            <div class="col-md-2"><label for="moneda" class="form-label">Moneda</label><select id="moneda" name="moneda" class="form-select">
                    <option>PEN</option>
                    <option>USD</option>
                </select></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Crear cuenta</button></div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Banco</th>
                        <th>Cuenta</th>
                        <th>CCI</th>
                        <th>Moneda</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuentas as $cuenta): ?><tr>
                            <td><?= esc($cuenta['banco']) ?></td>
                            <td><?= esc($cuenta['numerocuenta']) ?></td>
                            <td><?= esc($cuenta['cci'] ?? '—') ?></td>
                            <td><?= esc($cuenta['moneda']) ?></td>
                            <td><?= $cuenta['estado'] ? 'Activa' : 'Inactiva' ?></td>
                            <td>
                                <form method="post" action="<?= base_url('admin/caja/estado-cuenta/' . (int) $cuenta['idcuenta']) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="<?= $cuenta['estado'] ? 0 : 1 ?>"><button class="btn btn-sm btn-outline-secondary"><?= $cuenta['estado'] ? 'Desactivar' : 'Activar' ?></button></form>
                            </td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>