<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title><?= esc($cotizacion['numero']) ?></title>
<style>
@page { margin: 30px 34px 52px; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #352740; }
table { width: 100%; border-collapse: collapse; }
.cabecera { margin-bottom: 18px; border-bottom: 4px solid #ff2b50; }
.cabecera td { padding-bottom: 14px; vertical-align: top; }
.logo { width: 190px; height: auto; }
.marca { color: #32005a; font-size: 25px; font-weight: bold; }
.documento { text-align: right; color: #32005a; }
.documento strong { font-size: 19px; }
.numero { font-size: 11px; font-weight: bold; margin-top: 8px; }
.muted { color: #7b7083; font-size: 8px; }
.datos { margin-bottom: 18px; background: #f7f4fa; border: 1px solid #e7deef; }
.datos td { padding: 9px 12px; vertical-align: top; }
.etiqueta { color: #8a6a9f; font-size: 7px; font-weight: bold; text-transform: uppercase; }
.detalle thead { display: table-header-group; }
.detalle th { background: #32005a; color: white; text-align: left; padding: 10px 7px; font-size: 8px; }
.detalle td { padding: 10px 7px; border-bottom: 1px solid #e9e1ee; vertical-align: top; overflow-wrap: break-word; }
.detalle tr { page-break-inside: avoid; }
.derecha { text-align: right !important; }
.descripcion { width: 49%; }
.totales { width: 46%; margin: 18px 0 18px auto; page-break-inside: avoid; }
.totales td { padding: 7px 9px; border-bottom: 1px solid #e7deef; }
.total td { background: #32005a; color: white; font-size: 13px; font-weight: bold; border-bottom: 3px solid #ff2b50; }
.condiciones { margin-top: 14px; border-top: 1px solid #e7deef; padding-top: 10px; line-height: 1.6; }
.condiciones strong { color: #32005a; }
.pie { position: fixed; bottom: -30px; left: 0; right: 0; border-top: 2px solid #ff2b50; padding-top: 8px; color: #7b7083; font-size: 7px; text-align: center; }
.estado { display: inline-block; background: #fff0f4; color: #a33154; padding: 4px 9px; margin-top: 5px; }
</style></head><body>
<?php $c = $cotizacion; ?>
<table class="cabecera"><tr><td width="48%"><?php if ($logo): ?><img class="logo" src="<?= esc($logo, 'attr') ?>" alt="DELAFIBER"><?php else: ?><div class="marca">DELAFIBER</div><?php endif; ?><div class="muted">Equipos, conectividad y servicios técnicos</div><?php foreach (['empresa_ruc', 'empresa_direccion', 'empresa_telefono', 'empresa_email'] as $clave): ?><?php if (!empty($empresa[$clave])): ?><div class="muted"><?= esc($empresa[$clave]) ?></div><?php endif; ?><?php endforeach; ?></td><td class="documento"><strong>COTIZACIÓN</strong><div>PROFORMA COMERCIAL</div><div class="numero"><?= esc($c['numero']) ?></div><?php if ($c['estado_codigo'] === 'BORRADOR'): ?><span class="estado">BORRADOR</span><?php elseif ($c['vencida']): ?><span class="estado">VIGENCIA VENCIDA</span><?php endif; ?></td></tr></table>
<table class="datos"><tr><td width="60%"><span class="etiqueta">Cliente</span><br><strong><?= esc($cliente['nombre']) ?></strong><br><?= esc(($cliente['tipodoc'] ?? 'Documento') . ': ' . ($cliente['numerodoc'] ?? '')) ?><?php if (!empty($cliente['direccion'])): ?><br><?= esc($cliente['direccion']) ?><?php endif; ?><?php if (!empty($cliente['telefono'])): ?><br>Tel.: <?= esc($cliente['telefono']) ?><?php endif; ?></td><td><span class="etiqueta">Fecha</span><br><?= esc(date('d/m/Y', strtotime($c['fecha_emision'] ?: $c['fecha']))) ?><br><span class="etiqueta">Válida hasta</span><br><strong><?= esc(date('d/m/Y', strtotime($c['fechavencimiento']))) ?></strong></td></tr><tr><td><span class="etiqueta">Línea / Local</span><br><?= esc($c['linea'] . ' / ' . $c['nombrelocal']) ?></td><td><span class="etiqueta">Moneda de la propuesta</span><br>Soles (PEN)<br><span class="muted">TC ingresado: 1 USD = S/ <?= number_format((float) $c['tipocambio'], 4) ?></span></td></tr></table>
<table class="detalle"><thead><tr><th width="5%">#</th><th class="descripcion">Descripción</th><th class="derecha" width="12%">Cantidad</th><th class="derecha" width="16%">P. unitario S/</th><th class="derecha" width="18%">Importe S/</th></tr></thead><tbody><?php foreach ($detalles as $i => $d): ?><tr><td><?= $i + 1 ?></td><td><?= nl2br(esc($d['descripcion'] ?: $d['nombre'])) ?></td><td class="derecha"><?= number_format((float) $d['cantidad'], 2) ?></td><td class="derecha"><?= number_format((float) $d['preciounitario'], 2) ?></td><td class="derecha"><?= number_format(round((float) $d['cantidad'] * (float) $d['preciounitario'], 2), 2) ?></td></tr><?php endforeach; ?></tbody></table>
<p class="muted">Precios unitarios e importes finales en soles, con IGV incluido. Redondeo por precio unitario final.</p>
<table class="totales"><tr><td>BASE IMPONIBLE</td><td class="derecha">S/ <?= number_format((float) $c['subtotal'], 2) ?></td></tr><tr><td>IGV <?= number_format((float) $c['porcentajeigv'], 2) ?> %</td><td class="derecha">S/ <?= number_format((float) $c['montoigv'], 2) ?></td></tr><tr class="total"><td>TOTAL</td><td class="derecha">S/ <?= number_format((float) $c['total'], 2) ?></td></tr></table>
<?php if ($c['condiciones']): ?><div class="condiciones"><strong>CONDICIONES COMERCIALES</strong><br><?= nl2br(esc($c['condiciones'])) ?></div><?php endif; ?>
<?php if ($c['observacion']): ?><div class="condiciones"><strong>OBSERVACIONES</strong><br><?= nl2br(esc($c['observacion'])) ?></div><?php endif; ?>
<div class="pie">DELAFIBER · <?= esc($c['numero']) ?> · Propuesta sujeta a vigencia y disponibilidad al confirmar la compra.</div>
</body></html>
