<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
requerir_admin();

// El sistema opera con una fecha de referencia fija (datos semilla), no con la fecha real.
$fecha_referencia = fecha_referencia_sistema();

$rangos_atajo = [
    'mes' => [date('Y-m-01', strtotime($fecha_referencia)), $fecha_referencia],
    'trimestre' => [date('Y-m-01', strtotime($fecha_referencia . ' -3 months')), $fecha_referencia],
    'anio' => [date('Y-01-01', strtotime($fecha_referencia)), $fecha_referencia],
];

$atajo = is_string($_GET['atajo'] ?? null) ? $_GET['atajo'] : '';
$desde = normalizar_fecha_entrada(is_string($_GET['desde'] ?? null) ? $_GET['desde'] : '') ?? '2026-07-01';
$hasta = normalizar_fecha_entrada(is_string($_GET['hasta'] ?? null) ? $_GET['hasta'] : '') ?? '2026-10-25';

if (isset($rangos_atajo[$atajo])) {
    [$desde, $hasta] = $rangos_atajo[$atajo];
}

if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

$sector = is_string($_GET['sector'] ?? null) ? $_GET['sector'] : 'Todos';
if (!in_array($sector, ['Todos', 'Industrial', 'Comercial', 'Residencial'], true)) {
    $sector = 'Todos';
}

$atajo_activo = '';
foreach ($rangos_atajo as $clave => [$inicio, $fin]) {
    if ($inicio === $desde && $fin === $hasta) {
        $atajo_activo = $clave;
        break;
    }
}

$reportes = obtener_reportes($desde, $hasta, $sector);
$kpis = $reportes['kpis'];

// Se codifica con JSON_HEX_* para incrustarlo en <script type="application/json"> sin escapar comillas como entidades HTML.
$json_reportes = json_encode(
    [
        'fecha_referencia' => $fecha_referencia,
        'total' => (int)$kpis['total'],
        'i1_por_mes' => array_map(
            static fn(array $fila): array => ['etiqueta' => $fila['etiqueta'], 'cantidad' => (int)$fila['cantidad']],
            $reportes['i1_por_mes']
        ),
        'i2_por_canal' => array_map(
            static fn(array $fila): array => ['canal' => $fila['canal'], 'cantidad' => (int)$fila['cantidad'], 'porcentaje' => (int)$fila['porcentaje']],
            $reportes['i2_por_canal']
        ),
        'i3_por_estado' => array_map(
            static fn(array $fila): array => ['estado' => $fila['estado'], 'cantidad' => (int)$fila['cantidad'], 'porcentaje' => (float)$fila['porcentaje']],
            $reportes['i3_por_estado']
        ),
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '{}';

$total = (int)$kpis['total'];
$dias_promedio = number_format((float)$kpis['promedio_dias_visita'], 1, '.', '');

$titulo = 'Reportes';
$pagina_activa = 'reportes';
$variante_nav = 'app';
$body_class = 'pagina-app';
$ruta_raiz = '';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo">Reportes</h1>
  </div>
</div>

<form class="barra-filtros" id="form-reportes" method="get" action="reportes.php">
  <div class="barra-filtros__fila">
    <div class="campo campo--inline">
      <label class="campo__etiqueta" for="filtro-desde">Periodo</label>
      <div class="campos-grid campos-grid--2">
        <input class="campo__control" type="date" id="filtro-desde" name="desde" value="<?= e($desde) ?>">
        <label class="sr-only" for="filtro-hasta">Hasta</label>
        <input class="campo__control" type="date" id="filtro-hasta" name="hasta" value="<?= e($hasta) ?>" aria-label="Hasta">
      </div>
    </div>
    <div class="grupo-segmentado" role="group" aria-label="Atajos de periodo">
      <button type="button" class="grupo-segmentado__boton<?= $atajo_activo === 'mes' ? ' grupo-segmentado__boton--activo' : '' ?>" data-atajo-periodo="mes">Este mes</button>
      <button type="button" class="grupo-segmentado__boton<?= $atajo_activo === 'trimestre' ? ' grupo-segmentado__boton--activo' : '' ?>" data-atajo-periodo="trimestre">Últimos 3 meses</button>
      <button type="button" class="grupo-segmentado__boton<?= $atajo_activo === 'anio' ? ' grupo-segmentado__boton--activo' : '' ?>" data-atajo-periodo="anio">Este año</button>
    </div>
    <div class="campo campo--inline">
      <label class="campo__etiqueta" for="filtro-sector">Tipo de lugar</label>
      <select class="campo__control" id="filtro-sector" name="sector">
        <option value="Todos"<?= $sector === 'Todos' ? ' selected' : '' ?>>Todos</option>
        <option value="Industrial"<?= $sector === 'Industrial' ? ' selected' : '' ?>>Industrial</option>
        <option value="Comercial"<?= $sector === 'Comercial' ? ' selected' : '' ?>>Comercial</option>
        <option value="Residencial"<?= $sector === 'Residencial' ? ' selected' : '' ?>>Residencial</option>
      </select>
    </div>
    <div class="barra-filtros__acciones">
      <button type="submit" class="btn btn--primario btn--pequeno">Aplicar</button>
    </div>
  </div>
</form>

<section class="kpis-grid">
  <article class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Solicitudes</span>
    <strong class="tarjeta-kpi__valor"><?= e($total) ?></strong>
    <span class="tarjeta-kpi__subtexto"><?= e(formatear_fecha_corta($desde)) ?> – <?= e(formatear_fecha($hasta)) ?></span>
  </article>
  <article class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Por la web</span>
    <strong class="tarjeta-kpi__valor"><?= e((int)$kpis['web_porcentaje']) ?> %</strong>
    <span class="tarjeta-kpi__subtexto"><?= e((int)$kpis['web_cantidad']) ?> de <?= e($total) ?></span>
  </article>
  <article class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Hasta la visita</span>
    <strong class="tarjeta-kpi__valor"><?= e($dias_promedio) ?> días</strong>
    <span class="tarjeta-kpi__subtexto">Promedio</span>
  </article>
  <article class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Activas</span>
    <strong class="tarjeta-kpi__valor"><?= e((int)$kpis['activas']) ?></strong>
    <span class="tarjeta-kpi__subtexto">Sin cerrar</span>
  </article>
</section>

<?php if ($total === 0): ?>
<div class="estado-vacio">
  <p class="estado-vacio__titulo">Sin solicitudes en el periodo</p>
  <p class="estado-vacio__texto">Ajuste las fechas o el tipo de lugar para ver los indicadores.</p>
</div>
<?php endif; ?>

<section class="reportes-grid">

  <article class="tarjeta-reporte">
    <h2 class="tarjeta-reporte__titulo">I-1 · Solicitudes por mes</h2>
    <div class="tarjeta-reporte__lienzo">
      <canvas id="grafica-i1" width="520" height="220" role="img" aria-label="Gráfica de línea de solicitudes por mes"></canvas>
    </div>
    <div class="tabla-contenedor">
      <table class="tabla-datos">
        <caption>I-1 · Solicitudes por mes</caption>
        <thead>
          <tr>
            <th>Mes</th>
            <th>Solicitudes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reportes['i1_por_mes'] as $fila): ?>
          <tr>
            <td><?= e($fila['etiqueta']) ?></td>
            <td><?= e((int)$fila['cantidad']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="tarjeta-reporte__nota">Cálculo: solicitudes registradas en cada mes, según la fecha de registro.</p>
  </article>

  <article class="tarjeta-reporte">
    <h2 class="tarjeta-reporte__titulo">I-2 · Solicitudes por canal</h2>
    <div class="tarjeta-reporte__lienzo" id="grafica-i2">
      <?php foreach ($reportes['i2_por_canal'] as $fila): ?>
      <p>
        <strong><?= e($fila['canal']) ?></strong>
        <span class="barra-horizontal" style="width: <?= e((int)$fila['porcentaje']) ?>%"></span>
        <span><?= e((int)$fila['cantidad']) ?> · <?= e((int)$fila['porcentaje']) ?> %</span>
      </p>
      <?php endforeach; ?>
    </div>
    <div class="tabla-contenedor">
      <table class="tabla-datos">
        <caption>I-2 · Solicitudes por canal</caption>
        <thead>
          <tr>
            <th>Canal</th>
            <th>Solicitudes</th>
            <th>%</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reportes['i2_por_canal'] as $fila): ?>
          <tr>
            <td><?= e($fila['canal']) ?></td>
            <td><?= e((int)$fila['cantidad']) ?></td>
            <td><?= e((int)$fila['porcentaje']) ?> %</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="tarjeta-reporte__nota">Cálculo: solicitudes de cada canal ÷ total del periodo (<?= e($total) ?>).</p>
  </article>

  <article class="tarjeta-reporte">
    <h2 class="tarjeta-reporte__titulo">I-3 · Solicitudes por estado</h2>
    <div class="tarjeta-reporte__lienzo" id="grafica-i3">
      <div class="tabla-contenedor">
        <table class="tabla-datos">
          <caption>I-3 · Solicitudes por estado</caption>
          <thead>
            <tr>
              <th>Estado</th>
              <th>Cant.</th>
              <th>%</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reportes['i3_por_estado'] as $fila): ?>
            <tr>
              <td><?= e($fila['estado']) ?></td>
              <td><?= e((int)$fila['cantidad']) ?></td>
              <td><?= e(number_format((float)$fila['porcentaje'], 1, '.', '')) ?> %</td>
            </tr>
            <?php endforeach; ?>
            <tr>
              <td class="texto-negrita">Total</td>
              <td class="texto-negrita"><?= e($total) ?></td>
              <td class="texto-negrita">100 %</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <p class="tarjeta-reporte__nota">Cálculo: estado actual de cada solicitud del periodo. Activas = todas menos Finalizada, Rechazada y Cancelada (<?= e((int)$kpis['activas']) ?>).</p>
  </article>

  <article class="tarjeta-reporte">
    <h2 class="tarjeta-reporte__titulo">I-4 · Tiempo hasta la visita por tipo de lugar</h2>
    <div class="tarjeta-reporte__lienzo" id="grafica-i4">
      <div class="tabla-contenedor">
        <table class="tabla-datos">
          <caption>I-4 · Tiempo hasta la visita por tipo de lugar</caption>
          <thead>
            <tr>
              <th>Tipo de lugar</th>
              <th>Con visita</th>
              <th>Días promedio</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reportes['i4_tiempo_visita'] as $fila): ?>
            <tr>
              <td><?= e($fila['sector']) ?></td>
              <td><?= e((int)$fila['con_visita']) ?></td>
              <td><?= e(number_format((float)$fila['dias_promedio'], 1, '.', '')) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
              <td class="texto-negrita">Total</td>
              <td class="texto-negrita"><?= e((int)$kpis['total_con_visita']) ?></td>
              <td class="texto-negrita"><?= e($dias_promedio) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <p class="tarjeta-reporte__nota">Cálculo: promedio de días entre el registro y la fecha de visita; solo solicitudes con visita (<?= e((int)$kpis['total_con_visita']) ?> de <?= e($total) ?>).</p>
  </article>

</section>

<script type="application/json" id="reportes-datos"><?= $json_reportes ?></script>
<?php require __DIR__ . '/includes/footer.php';
