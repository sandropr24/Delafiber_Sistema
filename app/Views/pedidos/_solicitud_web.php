<?php if (!empty($datosWeb)): ?>
    <section class="card pedido-card pedido-web-card border shadow-sm rounded-3 mb-3">
        <div class="card-header pedido-section-heading fw-semibold"><span><i class="bi bi-globe2" aria-hidden="true"></i>Solicitud de la tienda web</span><small>Datos originales</small></div>
        <div class="card-body">
            <p class="pedido-web-intro small text-muted">Estos son los datos que el cliente indicó al enviar el pedido. Cualquier ajuste posterior quedará registrado por separado.</p>
            <dl class="pedido-web-data row g-3 mb-0">
                <div class="col-md-6"><dt>Modalidad solicitada</dt><dd><?= esc(['RECOJO' => 'Recojo en tienda', 'DOMICILIO' => 'Entrega a domicilio'][$datosWeb['modalidad'] ?? ''] ?? 'Por confirmar') ?></dd></div>
                <div class="col-md-6"><dt>Contacto</dt><dd class="text-break"><?= esc($datosWeb['nombre_contacto'] ?? '') ?></dd></div>
                <div class="col-md-6"><dt>Correo</dt><dd class="text-break"><?= esc($datosWeb['email_contacto'] ?? '') ?></dd></div>
                <div class="col-md-6"><dt>Teléfono original</dt><dd><?= esc($datosWeb['telefono_contacto'] ?? '') ?></dd></div>
                <?php if (($datosWeb['modalidad'] ?? '') === 'DOMICILIO'): ?>
                    <div class="col-12 pedido-web-address"><dt>Dirección original</dt><dd class="text-break"><?= nl2br(esc($datosWeb['direccion_entrega'] ?? '')) ?></dd></div>
                <?php endif; ?>
                <?php if (!empty($observacionesPedido)): ?>
                    <div class="col-12 pedido-web-address"><dt>Observaciones del pedido</dt><dd class="text-break"><?= nl2br(esc($observacionesPedido)) ?></dd></div>
                <?php endif; ?>
            </dl>
        </div>
    </section>
<?php endif; ?>
