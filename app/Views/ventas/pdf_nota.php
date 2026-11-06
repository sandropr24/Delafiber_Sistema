<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nota de venta</title>
    <style>
        <?= $estilosPdf ?? '' ?>
    </style>
</head>

<body>
    <table class="cabecera">
        <tr>
            <td width="55%">
                <?php if (!empty($logo)): ?>
                    <img src="<?= esc($logo, 'attr') ?>" alt="DELAFIBER" class="logo">
                <?php else: ?>
                    <div class="empresa">DELAFIBER</div>
                <?php endif; ?>
                <div class="subtitulo">Equipos y servicios técnicos</div>
            </td>
            <td class="documento">
                <strong>NOTA DE VENTA</strong>
                <div class="numero"><?= esc($venta['numero'] ?? '') ?></div>
                <div class="subtitulo">Documento interno</div>
            </td>
        </tr>
    </table>

    <table class="datos">
        <tr>
            <td width="55%"><span class="etiqueta">CLIENTE</span><br><strong><?= esc(trim($venta['cliente'] ?? '') ?: ($venta['cliente_nombre'] ?? 'Cliente')) ?></strong></td>
            <td><span class="etiqueta">FECHA</span><br><?= esc(!empty($venta['fecha']) ? date('d/m/Y H:i', strtotime($venta['fecha'])) : '-') ?></td>
        </tr>
        <tr>
            <td><span class="etiqueta">DOCUMENTO DEL CLIENTE</span><br><?= esc($venta['numerodoc'] ?? ($venta['cliente_documento'] ?? '-')) ?></td>
            <td><span class="etiqueta">LOCAL</span><br><?= esc($venta['local'] ?? '-') ?></td>
        </tr>
    </table>

    <table class="detalle">
        <thead>
            <tr>
                <th>Producto / servicio</th>
                <th class="derecha">Cantidad</th>
                <th class="derecha">P. unitario</th>
                <th class="derecha">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $d): ?>
                <?php $cantidad = (float) ($d['cantidad'] ?? 0);
                $precio = (float) ($d['precioventa'] ?? 0); ?>
                <tr>
                    <td><?= esc($d['descripcionventa'] ?? ($d['producto'] ?? 'Producto')) ?></td>
                    <td class="derecha"><?= number_format($cantidad, 2) ?></td>
                    <td class="derecha">S/ <?= number_format($precio, 2) ?></td>
                    <td class="derecha">S/ <?= number_format(isset($d['total']) ? (float)$d['total'] : $cantidad * $precio, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totales">
        <tr class="total">
            <td>TOTAL</td>
            <td class="derecha">S/ <?= number_format((float) ($venta['total'] ?? 0), 2) ?></td>
        </tr>
    </table>

    <?php if (!empty($pagos)): ?>
        <div class="pagos"><strong>Medios de pago</strong></div>
        <?php foreach ($pagos as $pago): ?>
            <div><?= esc($pago['tipopago'] ?? 'Pago') ?>: S/ <?= number_format((float) ($pago['monto'] ?? 0), 2) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="pie">Gracias por su compra.</div>
</body>

</html>