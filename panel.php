<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
$usuario = requerir_login();
$es_admin = es_admin();

// Eliminación por el cliente: solo sobre solicitudes propias que sigan Enviadas.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    if ($es_admin || ($_POST['accion'] ?? '') !== 'eliminar') {
        flash_establecer('error', 'Acción no permitida.');
    } elseif (eliminar_solicitud_cliente((int) ($_POST['id_solicitud'] ?? 0), (int) $usuario['id_usuario'])) {
        flash_establecer('exito', 'Solicitud eliminada.');
    } else {
        flash_establecer('error', 'No se pudo eliminar: la solicitud no existe o ya está en revisión.');
    }
    header('Location: panel.php');
    exit;
}

$clase_estado = static fn(string $estado): string => str_replace(['é', ' '], ['e', '-'], mb_strtolower($estado));
$pagina_activa = 'panel';
$variante_nav = 'app';
$body_class = 'pagina-app';

if ($es_admin) {
    $resumen = obtener_resumen_panel_admin();
    $atender = listar_atender_primero();

    $titulo = 'Panel';
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/nav.php';
    ?>
<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo">Panel</h1>
    <p class="encabezado-pagina__subtitulo"><?= e($resumen['fecha_encabezado']) ?></p>
  </div>
</div>

<section class="kpis-grid">
  <article class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Nuevas</span>
    <strong class="tarjeta-kpi__valor"><?= e($resumen['nuevas']) ?></strong>
    <span class="tarjeta-kpi__subtexto">Estado: Enviada</span>
  </article>
  <article class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">En revisión</span>
    <strong class="tarjeta-kpi__valor"><?= e($resumen['en_revision']) ?></strong>
    <span class="tarjeta-kpi__subtexto">Pendientes de agendar</span>
  </article>
  <article class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Visitas próximas</span>
    <strong class="tarjeta-kpi__valor"><?= e($resumen['visitas_proximas']) ?></strong>
    <span class="tarjeta-kpi__subtexto">Próximos 7 días</span>
  </article>
  <article class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Recibidas en <?= e($resumen['mes_nombre']) ?></span>
    <strong class="tarjeta-kpi__valor"><?= e($resumen['recibidas_mes']) ?></strong>
    <span class="tarjeta-kpi__subtexto"><?= e($resumen['rango_mes_texto']) ?></span>
  </article>
</section>

<div class="encabezado-pagina">
  <div>
    <h2 class="encabezado-pagina__titulo">Atender primero</h2>
    <p class="encabezado-pagina__subtitulo">Solicitudes enviadas, por prioridad</p>
  </div>
  <div class="encabezado-pagina__acciones">
    <a href="index.php" class="btn btn--enlace">Ver todas</a>
  </div>
</div>

    <?php if ($atender === []): ?>
<div class="estado-vacio">
  <p class="estado-vacio__titulo">No hay solicitudes por atender</p>
  <p class="estado-vacio__texto">Las solicitudes nuevas aparecerán aquí.</p>
</div>
    <?php else: ?>
<ul class="lista-paleta lista-paleta--panel">
  <?php foreach ($atender as $s): ?>
    <li class="lista-paleta__fila">
      <span class="badge-prioridad badge-prioridad--<?= e(mb_strtolower(nivel_prioridad((int) $s['prioridad']))) ?>"><?= e(formatear_prioridad((int) $s['prioridad'])) ?></span>
      <span class="lista-paleta__principal">
        <span class="lista-paleta__titulo"><span class="texto-mono texto-negrita"><?= e($s['folio']) ?></span> · <?= e($s['usuario_nombre']) ?></span>
        <span class="lista-paleta__sub"><?= e($s['servicio_nombre']) ?> · <?= e($s['sector']) ?> · <?= e($s['escala']) ?></span>
      </span>
      <span class="lista-paleta__meta"><?= e(formatear_fecha_relativa((string) $s['fecha_registro'])) ?></span>
      <a href="editar.php?folio=<?= e(rawurlencode((string) $s['folio'])) ?>" class="btn btn--secundario btn--pequeno">Revisar</a>
    </li>
  <?php endforeach; ?>
</ul>
    <?php endif; ?>

<section class="kpis-grid">
  <a href="index.php" class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Solicitudes</span>
    <span class="tarjeta-kpi__subtexto">Buscar, revisar y cambiar estados</span>
  </a>
  <a href="reportes.php" class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Reportes</span>
    <span class="tarjeta-kpi__subtexto">Indicadores del periodo</span>
  </a>
  <a href="usuarios.php" class="tarjeta-kpi">
    <span class="tarjeta-kpi__titulo">Usuarios</span>
    <span class="tarjeta-kpi__subtexto">Cuentas y administradores</span>
  </a>
</section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Cliente: lista propia o detalle de una solicitud (?ver=FOLIO).
$etapas = etapas_seguimiento();
$historicos = ['Finalizada', 'Rechazada', 'Cancelada'];
$id_cliente = (int) $usuario['id_usuario'];

$folio_ver = is_string($_GET['ver'] ?? null) ? trim($_GET['ver']) : '';
$detalle = null;
if ($folio_ver !== '') {
    $detalle = obtener_solicitud_cliente($id_cliente, $folio_ver);
    if ($detalle === null) {
        flash_establecer('error', 'No se encontró la solicitud.');
        header('Location: panel.php');
        exit;
    }
    $solicitudes = [$detalle];
} else {
    $solicitudes = listar_solicitudes_cliente($id_cliente);
}

// Una tarjeta con línea de tiempo; $detallada añade descripción y ubicación.
$tarjeta = static function (array $s, bool $detallada) use ($etapas, $clase_estado): void {
    $indice = array_search($s['estado'], $etapas, true);
    $editable = $s['estado'] === 'Enviada';
    ?>
    <article class="tarjeta-solicitud">
      <header class="tarjeta-solicitud__cabecera">
        <div>
          <strong class="tarjeta-solicitud__folio"><?= e($s['folio']) ?></strong>
          <p class="tarjeta-solicitud__subtitulo"><?= e($s['servicio_nombre']) ?> · <?= e($s['sector']) ?> · <?= e($s['escala']) ?> · Registrada <?= e(formatear_fecha($s['fecha_registro'])) ?></p>
        </div>
        <span class="badge-estado badge-estado--<?= e($clase_estado((string) $s['estado'])) ?>"><?= e($s['estado']) ?></span>
      </header>

      <?php if ($indice !== false): ?>
        <ol class="linea-etapas" aria-label="Progreso de la solicitud">
          <?php foreach ($etapas as $i => $etapa): ?>
            <?php $estado_paso = $i < $indice ? 'completado' : ($i === $indice ? 'activo' : 'pendiente'); ?>
            <li class="linea-etapas__paso linea-etapas__paso--<?= e($estado_paso) ?>"<?= $estado_paso === 'activo' ? ' aria-current="step"' : '' ?>>
              <span class="linea-etapas__nodo"></span>
              <span class="linea-etapas__etiqueta"><?= e($etapa) ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>

      <?php if (!empty($s['fecha_visita'])): ?>
        <div class="tarjeta-solicitud__visita">
          <strong>Visita: <?= e(formatear_fecha_visita_larga($s['fecha_visita'])) ?></strong>
        </div>
      <?php endif; ?>

      <?php if ($detallada): ?>
        <p><strong>Descripción:</strong> <?= e($s['descripcion']) ?></p>
        <p><strong>Ubicación:</strong> <?= e($s['ubicacion']) ?></p>
      <?php endif; ?>

      <?php if ($editable): ?>
        <footer class="tarjeta-solicitud__pie">
          <span class="tarjeta-solicitud__aviso">Puede modificarla o eliminarla mientras esté Enviada.</span>
          <a href="crear.php?folio=<?= e(rawurlencode((string) $s['folio'])) ?>" class="btn btn--enlace">Editar</a>
          <form method="post" action="panel.php" data-confirmar="¿Desea eliminar esta solicitud? No se puede deshacer.">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_solicitud" value="<?= e($s['id_solicitud']) ?>">
            <button type="submit" class="btn btn--enlace">Eliminar</button>
          </form>
        </footer>
      <?php endif; ?>
    </article>
    <?php
};

$titulo = $detalle !== null ? 'Solicitud ' . $detalle['folio'] : 'Mis solicitudes';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<div class="layout-70-30">
  <div class="layout-70-30__principal">
    <?php if ($detalle !== null): ?>
      <nav class="breadcrumbs" aria-label="Ruta de navegación">
        <a href="panel.php" class="breadcrumbs__enlace">Mis solicitudes</a>
        <span class="breadcrumbs__separador">/</span>
        <span class="breadcrumbs__actual"><?= e($detalle['folio']) ?></span>
      </nav>
      <?php $tarjeta($detalle, true); ?>
    <?php else: ?>
      <div class="encabezado-pagina">
        <div>
          <h1 class="encabezado-pagina__titulo">Mis solicitudes</h1>
          <p class="encabezado-pagina__subtitulo"><?= e(count($solicitudes)) ?> <?= count($solicitudes) === 1 ? 'solicitud' : 'solicitudes' ?></p>
        </div>
        <div class="encabezado-pagina__acciones">
          <a href="crear.php" class="btn btn--primario">Nueva solicitud</a>
        </div>
      </div>

      <?php if ($solicitudes === []): ?>
        <div class="estado-vacio">
          <p class="estado-vacio__titulo">Aún no tiene solicitudes</p>
          <p class="estado-vacio__texto">Cuando registre una, aquí verá en qué etapa va.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($solicitudes as $s): ?>
        <?php if (in_array($s['estado'], $historicos, true)): ?>
          <article class="tarjeta-solicitud">
            <header class="tarjeta-solicitud__cabecera">
              <div>
                <strong class="tarjeta-solicitud__folio"><?= e($s['folio']) ?></strong>
                <p class="tarjeta-solicitud__subtitulo"><?= e($s['servicio_nombre']) ?> · <?= e($s['sector']) ?> · Registrada <?= e(formatear_fecha($s['fecha_registro'])) ?></p>
              </div>
              <span class="badge-estado badge-estado--<?= e($clase_estado((string) $s['estado'])) ?>"><?= e($s['estado']) ?></span>
              <a href="panel.php?ver=<?= e(rawurlencode((string) $s['folio'])) ?>">Ver detalle</a>
            </header>
          </article>
        <?php else: ?>
          <?php $tarjeta($s, false); ?>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <aside class="layout-70-30__lateral">
    <div class="tarjeta-lateral">
      <h3 class="tarjeta-lateral__titulo">Qué sigue</h3>
      <ol>
        <li><strong>Revisión</strong><p>OEM revisa los datos de la solicitud y confirma el alcance del trabajo.</p></li>
        <li><strong>Visita sin costo</strong><p>Un técnico acude al lugar para inspeccionar la instalación.</p></li>
        <li><strong>Presupuesto</strong><p>Con lo visto en la visita se prepara el presupuesto del trabajo.</p></li>
      </ol>
      <p>(664) 000 0000<br>contacto@ejemplo.com</p>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
