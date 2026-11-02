<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('contenido') ?>

<?php
    $indicadores = [
        ['Ventas de hoy', 'S/ ' . number_format((float) ($totalVentasHoy ?? 0), 2), 'bi-cash-stack'],
        ['Alertas de stock', (string) ($stockCritico ?? 0), 'bi-exclamation-triangle'],
        ['Productos activos', (string) ($totalProductos ?? 0), 'bi-box-seam'],
        ['Cotizaciones pendientes', (string) ($cotizacionesPendientes ?? 0), 'bi-file-earmark-text'],
        ['Entregas pendientes', (string) ($entregasPendientes ?? 0), 'bi-truck']
    ];
?>

<div class="container-fluid px-3 px-lg-4 py-3 df-dashboard">
    <div class="df-card p-3 p-lg-4 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h4 class="fw-bold mb-1">Panel de control</h4>
            <div class="df-sub">¡Hola, <?= esc($nombreUsuario ?? 'Usuario') ?>! · Resumen comercial de DELAFIBER</div>
        </div>
        <div class="small text-muted">
            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y') ?>
            <span class="badge ms-2 df-role"><?= esc($rol ?? '') ?></span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <?php foreach ($indicadores as $indicador): ?>
            <div class="col-12 col-sm-6 col-xl">
                <div class="df-card df-kpi d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <small><?= esc($indicador[0]) ?></small>
                        <strong><?= esc($indicador[1]) ?></strong>
                    </div>
                    <div class="df-icon">
                        <i class="bi <?= esc($indicador[2]) ?>"></i>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-7">
            <div class="df-card p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="df-heading">Evolución de ventas</h5>
                        <div class="df-sub">Importes registrados · últimos 7 días</div>
                    </div>
                    <i class="bi bi-graph-up-arrow df-accent"></i>
                </div>
                <div class="df-chart">
                    <canvas id="dfVentasChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="df-card p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="df-heading">Productos más vendidos</h5>
                        <div class="df-sub">Top 5 por unidades vendidas · histórico</div>
                    </div>
                    <i class="bi bi-trophy df-accent"></i>
                </div>

                <?php if (empty($masVendidos)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                        Aún no hay productos vendidos.
                    </div>
                <?php else: ?>
                    <?php foreach ($masVendidos as $i => $producto): ?>
                        <div class="d-flex align-items-center gap-3 py-2 <?= $i ? 'border-top' : '' ?>">
                            <div class="df-rank"><?= $i + 1 ?></div>
                            <div class="flex-grow-1 df-min-width-0">
                                <div class="fw-semibold text-truncate small" title="<?= esc($producto['nombre']) ?>"><?= esc($producto['nombre']) ?></div>
                                <div class="df-sub">S/ <?= number_format((float) $producto['importe'], 2) ?> en ventas</div>
                            </div>
                            <div class="text-end">
                                <strong><?= number_format((float) $producto['unidades'], 0) ?></strong>
                                <div class="df-sub">unidades</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="df-card p-3 mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-bold small me-2">Accesos rápidos</span>
            <a href="<?= base_url('ventas') ?>" class="df-action"><i class="bi bi-cart-plus me-1"></i> Ventas</a>
            <a href="<?= base_url('cotizaciones') ?>" class="df-action"><i class="bi bi-file-earmark-plus me-1"></i> Cotizaciones</a>
            <a href="<?= base_url('productos') ?>" class="df-action"><i class="bi bi-box-seam me-1"></i> Productos</a>
            <a href="<?= base_url('entregas') ?>" class="df-action"><i class="bi bi-truck me-1"></i> Entregas</a>
        </div>
    </div>

    <div class="df-card overflow-hidden mb-3">
        <div class="p-3 d-flex justify-content-between align-items-center">
            <h5 class="df-heading">Últimas ventas registradas</h5>
            <a href="<?= base_url('ventas') ?>" class="df-link">Ver todas <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Comprobante</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th class="text-end">Total</th>
                        <th class="text-center pe-3">Documento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimasVentas)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay ventas registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ultimasVentas as $venta): ?>
                            <?php
                                $comprobante = ($venta['tipodocumento'] ?? '') . ' ' . ($venta['serie'] ?? '') . '-' . ($venta['numero'] ?? '');
                                $cliente = trim(($venta['nombres'] ?? '') . ' ' . ($venta['apellidos'] ?? ''));
                                if ($cliente === '') {
                                    $cliente = $venta['razonsocial'] ?? 'Sin nombre';
                                }
                            ?>
                            <tr>
                                <td class="ps-3 fw-semibold"><?= esc($comprobante) ?></td>
                                <td><?= esc($cliente) ?></td>
                                <td class="text-muted"><?= esc(date('d/m/Y H:i', strtotime($venta['fecha']))) ?></td>
                                <td class="text-end fw-bold">S/ <?= number_format((float) $venta['total'], 2) ?></td>
                                <td class="text-center pe-3">
                                    <span class="badge bg-light text-secondary border">
                                        <?= !empty($venta['estafacturado']) ? 'Facturado' : 'Emitido' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('dfVentasChart');

        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const dias = <?= json_encode($graficoDias ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const montos = <?= json_encode($graficoMontos ?? [], JSON_NUMERIC_CHECK) ?>;

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: dias,
                datasets: [
                    {
                        label: 'Ventas (S/)',
                        data: montos,
                        borderColor: '#ff254b',
                        backgroundColor: 'rgba(255, 37, 75, 0.09)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#ff254b'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (contexto) {
                                return 'S/ ' + Number(contexto.parsed.y).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0edf2' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>
<?= $this->endSection() ?>