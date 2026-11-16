<?php
$numeroNota = trim((string) ($venta['serie'] ?? '') . '-' . (string) ($venta['numero'] ?? ''), '-');
$clienteNota = trim((string) ($venta['cliente'] ?? '')) ?: ($venta['cliente_nombre'] ?? 'Cliente');
$subtotalNota = (float) ($venta['subtotal'] ?? 0);
if ($subtotalNota <= 0) {
    foreach ($detalles as $detalleNota) {
        $subtotalNota += round((float) $detalleNota['cantidad'] * (float) $detalleNota['precioventa'], 2);
    }
}
$mostrarEfectivo = false;
foreach ($pagos ?? [] as $pagoNota) {
    if ((float) ($pagoNota['montorecibido'] ?? 0) > 0 || (float) ($pagoNota['vuelto'] ?? 0) > 0) {
        $mostrarEfectivo = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nota de venta <?= esc($numeroNota) ?></title>
    <style>
        <?= $estilosPdf ?? '' ?>
    </style>
</head>

<body>
    <div class="pie">
        <strong>DELAFIBER</strong> &nbsp; / &nbsp; Documento interno<?= $numeroNota !== '' ? ' · ' . esc($numeroNota) : '' ?>
        <div>Este documento no sustituye una boleta o factura.</div>
    </div>

    <table class="cabecera">
        <tr>
            <td class="identidad">
                <?php if (!empty($logo)): ?>
                    <img src="<?= esc($logo, 'attr') ?>" alt="DELAFIBER" class="logo">
                <?php else: ?>
                    <div class="empresa">DELAFIBER</div>
                <?php endif; ?>
                <div class="subtitulo">Equipos, conectividad y servicios técnicos</div>
                <?php if (!empty($empresa['empresa_ruc'])): ?>
                    <div class="empresa-dato"><strong>RUC</strong> <?= esc($empresa['empresa_ruc']) ?></div>
                <?php endif; ?>
                <?php if (!empty($empresa['empresa_direccion'])): ?>
                    <div class="empresa-dato"><?= esc($empresa['empresa_direccion']) ?></div>
                <?php endif; ?>
                <?php if (!empty($empresa['empresa_telefono']) || !empty($empresa['empresa_email'])): ?>
                    <div class="empresa-dato"><?= esc(implode(' · ', array_filter([
                        $empresa['empresa_telefono'] ?? '',
                        $empresa['empresa_email'] ?? '',
                    ]))) ?></div>
                <?php endif; ?>
            </td>
            <td class="documento">
                <div class="documento-panel">
                    <div class="documento-etiqueta">DOCUMENTO INTERNO</div>
                    <h1>NOTA DE VENTA</h1>
                    <div class="numero"><?= esc($numeroNota !== '' ? $numeroNota : 'Sin numeración') ?></div>
                    <div class="documento-moneda">Importes en soles · PEN</div>
                </div>
            </td>
        </tr>
    </table>
    <div class="marca-linea"></div>

    <table class="datos">
        <tr>
            <td class="datos-cliente">
                <div class="etiqueta">CLIENTE / RAZÓN SOCIAL</div>
                <div class="cliente-nombre"><?= esc($clienteNota) ?></div>
                <div class="cliente-documento"><?= esc($venta['tipodoc'] ?? 'Documento') ?>: <?= esc($venta['numerodoc'] ?? ($venta['cliente_documento'] ?? 'No registrado')) ?></div>
                <?php if (!empty($venta['cliente_direccion'])): ?>
                    <div class="cliente-direccion"><?= esc($venta['cliente_direccion']) ?></div>
                <?php endif; ?>
            </td>
            <td class="datos-operacion">
                <div class="etiqueta">FECHA DE EMISIÓN</div>
                <div class="dato-valor"><?= esc(!empty($venta['fecha']) ? date('d/m/Y H:i', strtotime($venta['fecha'])) : 'No registrada') ?></div>
                <div class="etiqueta etiqueta-local">LOCAL DE ATENCIÓN</div>
                <div class="dato-valor"><?= esc($venta['local'] ?? 'No registrado') ?></div>
            </td>
        </tr>
    </table>

    <table class="seccion-titulo">
        <tr>
            <td><h2>Detalle de la operación</h2></td>
            <td class="derecha"><?= count($detalles) ?> <?= count($detalles) === 1 ? 'artículo' : 'artículos' ?></td>
        </tr>
    </table>
    <table class="detalle">
        <thead>
            <tr>
                <th class="item-numero" style="width: 5%">#</th>
                <th style="width: 41%">Producto / servicio</th>
                <th class="derecha" style="width: 14%">Cantidad</th>
                <th class="derecha" style="width: 20%">P. unitario</th>
                <th class="derecha" style="width: 20%">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $indiceNota => $d): ?>
                <?php $cantidad = (float) ($d['cantidad'] ?? 0);
                $precio = (float) ($d['precioventa'] ?? 0); ?>
                <tr>
                    <td class="item-numero"><?= $indiceNota + 1 ?></td>
                    <td class="item-descripcion"><?= nl2br(esc($d['descripcionventa'] ?? ($d['producto'] ?? 'Producto'))) ?></td>
                    <td class="derecha cifra"><?= number_format($cantidad, 2) ?></td>
                    <td class="derecha cifra">S/ <?= number_format($precio, 2) ?></td>
                    <td class="derecha cifra item-importe">S/ <?= number_format(isset($d['total']) ? (float)$d['total'] : $cantidad * $precio, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($detalles)): ?>
                <tr><td colspan="5" class="sin-detalle">No hay artículos asociados a esta nota.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="resumen">
        <tr>
            <td class="resumen-nota">
                <div class="etiqueta">RESUMEN DE LA OPERACIÓN</div>
                <p>Importes expresados en soles (PEN).</p>
                <p>Conserva el número de esta nota para cualquier consulta sobre tu compra.</p>
                <div class="agradecimiento">Gracias por elegir Delafiber.</div>
            </td>
            <td class="resumen-importes">
                <table class="totales">
                    <tr>
                        <td>Subtotal</td>
                        <td class="derecha cifra">S/ <?= number_format($subtotalNota, 2) ?></td>
                    </tr>
                    <?php if ((float) ($venta['descuento'] ?? 0) > 0): ?>
                        <tr class="descuento">
                            <td>Descuento<?= ($venta['tipodescuento'] ?? '') === 'PORCENTAJE' ? ' (' . number_format((float) ($venta['valordescuento'] ?? 0), 2) . '%)' : '' ?></td>
                            <td class="derecha cifra">− S/ <?= number_format((float) $venta['descuento'], 2) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total">
                        <td>Total de la venta</td>
                        <td class="derecha cifra">S/ <?= number_format((float) ($venta['total'] ?? 0), 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <?php if (!empty($pagos)): ?>
        <table class="pagos">
            <thead>
                <tr><th colspan="<?= $mostrarEfectivo ? 4 : 2 ?>" class="pagos-titulo">Detalle de pagos</th></tr>
                <tr>
                    <th>Medio de pago</th>
                    <th class="derecha">Monto aplicado</th>
                    <?php if ($mostrarEfectivo): ?>
                        <th class="derecha">Recibido</th>
                        <th class="derecha">Vuelto</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><?= esc($pago['tipopago'] ?? 'Pago') ?></td>
                        <td class="derecha cifra">S/ <?= number_format((float) ($pago['monto'] ?? 0), 2) ?></td>
                        <?php if ($mostrarEfectivo): ?>
                            <td class="derecha cifra"><?= isset($pago['montorecibido']) ? 'S/ ' . number_format((float) $pago['montorecibido'], 2) : '—' ?></td>
                            <td class="derecha cifra"><?= isset($pago['vuelto']) ? 'S/ ' . number_format((float) $pago['vuelto'], 2) : '—' ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>

</html>
