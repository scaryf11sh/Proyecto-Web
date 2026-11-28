<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
$usuario = requerir_admin();

$buscar = static function (string $folio, int $id): ?array {
    if ($folio !== '') {
        return obtener_solicitud_por_folio($folio);
    }
    return $id > 0 ? obtener_solicitud_por_id($id) : null;
};
$campo_texto = static fn(string $clave): string => is_string($_POST[$clave] ?? null) ? trim($_POST[$clave]) : '';

$es_post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($es_post) {
    exigir_csrf();
    $solicitud = $buscar('', (int) ($_POST['id_solicitud'] ?? 0));
    $solo_lectura = false;
} else {
    $folio = is_string($_GET['folio'] ?? null) ? trim($_GET['folio']) : '';
    $solicitud = $buscar($folio, (int) ($_GET['id'] ?? 0));
    $solo_lectura = ($_GET['modo'] ?? '') === 'ver';
    if ($solo_lectura && $solicitud !== null && $solicitud['estado'] === 'Enviada'
        && marcar_en_revision((int) $solicitud['id_solicitud'])) {
        $solicitud['estado'] = 'En revisión';
        flash_establecer('exito', 'La solicitud pasó a En revisión.');
    }
}

if ($solicitud === null) {
    flash_establecer('error', 'No se encontró la solicitud.');
    header('Location: index.php');
    exit;
}

$estado_actual = (string) $solicitud['estado'];
$permitidos = estados_permitidos($estado_actual);
$errores = [];

$datos = [
    'estado' => '',
    'fecha_visita' => !empty($solicitud['fecha_visita']) ? date('Y-m-d\TH:i', (int) strtotime((string) $solicitud['fecha_visita'])) : '',
    'notas_internas' => (string) ($solicitud['notas_internas'] ?? ''),
];

if ($es_post) {
    foreach (array_keys($datos) as $clave) {
        $datos[$clave] = $campo_texto($clave);
    }

    // El estado vacío significa "sin cambio"; el backend valida la transición de todos modos.
    $estado_enviado = $datos['estado'] !== '' ? $datos['estado'] : $estado_actual;
    $resultado = actualizar_solicitud(
        (int) $solicitud['id_solicitud'],
        array_merge($datos, ['estado' => $estado_enviado])
    );
    if ($resultado['ok']) {
        flash_establecer('exito', 'Solicitud ' . $solicitud['folio'] . ' actualizada.');
        header('Location: index.php');
        exit;
    }
    $errores = $resultado['errores'];
}

$prioridad = (int) $solicitud['prioridad'];

$dis = $solo_lectura ? ' disabled' : '';
$clase_estado = static fn(string $estado): string => str_replace(['é', ' '], ['e', '-'], mb_strtolower($estado));
$folio_url = rawurlencode((string) $solicitud['folio']);

$titulo = ($solo_lectura ? 'Solicitud ' : 'Editar solicitud ') . $solicitud['folio'];
$pagina_activa = 'solicitudes';
$variante_nav = 'app';
$body_class = 'pagina-app';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<div class="drawer-layout">
  <aside class="drawer-panel" aria-labelledby="drawer-titulo">
    <header class="drawer-panel__encabezado">
      <div>
        <h1 class="drawer-panel__titulo" id="drawer-titulo"><?= $solo_lectura ? 'Solicitud' : 'Editar solicitud' ?> <?= e($solicitud['folio']) ?></h1>
        <p class="drawer-panel__meta">Registrada <?= e(formatear_fecha($solicitud['fecha_registro'])) ?> · Cuenta: <?= e($solicitud['usuario_nombre']) ?></p>
      </div>
      <a href="index.php" class="drawer-panel__cerrar" aria-label="Cerrar">×</a>
    </header>

    <form method="post" action="editar.php" class="drawer-panel__cuerpo" data-validar="solicitud" novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="id_solicitud" value="<?= e($solicitud['id_solicitud']) ?>">
      <input type="hidden" name="folio" value="<?= e($solicitud['folio']) ?>">

      <?php if (isset($errores['solicitud'])): ?>
        <div class="alerta-flash alerta-flash--error" role="alert"><?= e($errores['solicitud']) ?></div>
      <?php endif; ?>

      <section class="drawer-seccion">
        <h2 class="drawer-seccion__titulo">Contacto para la visita</h2>
        <p><strong>Nombre:</strong> <?= e($solicitud['usuario_nombre']) ?></p>
        <p><strong>Teléfono:</strong> <?= e(formatear_telefono($solicitud['usuario_telefono'])) ?></p>
        <p><strong>Correo:</strong> <?= e($solicitud['usuario_correo']) ?></p>
      </section>

      <section class="drawer-seccion">
        <h2 class="drawer-seccion__titulo">Trabajo</h2>
        <p><strong>Tipo de lugar:</strong> <?= e($solicitud['sector']) ?> · <strong>Tamaño:</strong> <?= e($solicitud['escala']) ?></p>
        <p><strong>Servicio:</strong> <?= e($solicitud['servicio_nombre']) ?></p>
        <p><strong>Descripción:</strong> <?= e($solicitud['descripcion']) ?></p>
        <p><strong>Ubicación:</strong> <?= e($solicitud['ubicacion']) ?></p>
      </section>

      <section class="drawer-seccion">
        <h2 class="drawer-seccion__titulo">Prioridad</h2>
        <div class="caja-prioridad" id="caja-prioridad-calculada">
          <strong class="caja-prioridad__valor" id="texto-prioridad-calculada">Prioridad: <?= e(formatear_prioridad($prioridad)) ?> (calculada)</strong>
          <span class="caja-prioridad__ayuda">Se calcula con el tamaño y el tipo de lugar.</span>
        </div>
      </section>

      <section class="drawer-seccion">
        <h2 class="drawer-seccion__titulo">Seguimiento</h2>
        <p>Estado actual <span class="badge-estado badge-estado--<?= e($clase_estado($estado_actual)) ?>"><?= e($estado_actual) ?></span></p>

        <?php if ($permitidos !== [] && !$solo_lectura): ?>
          <div class="campo <?= isset($errores['estado']) ? 'campo--error' : '' ?>">
            <label class="campo__etiqueta" for="estado">Cambiar a</label>
            <select class="campo__control" id="estado" name="estado">
              <option value="">Mantener estado actual</option>
              <?php foreach ($permitidos as $opcion): ?>
                <option value="<?= e($opcion) ?>" <?= $datos['estado'] === $opcion ? 'selected' : '' ?>><?= e($opcion) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="campo__ayuda">Solo se muestran los estados permitidos desde <?= e($estado_actual) ?>.</span>
            <?php if (isset($errores['estado'])): ?>
              <span class="campo__error">! <?= e($errores['estado']) ?></span>
            <?php endif; ?>
          </div>
        <?php elseif ($permitidos === []): ?>
          <p class="campo__ayuda">Esta solicitud ya no admite cambios de estado.</p>
        <?php endif; ?>
        <?php if ($estado_actual === 'Presupuesto'): ?>
          <p class="campo__ayuda">Esperando que el cliente acepte el presupuesto.</p>
        <?php endif; ?>

        <div class="campo <?= isset($errores['fecha_visita']) ? 'campo--error' : '' ?>">
          <label class="campo__etiqueta" for="fecha_visita">Fecha de visita*</label>
          <input class="campo__control" type="datetime-local" id="fecha_visita" name="fecha_visita"
                 value="<?= e($datos['fecha_visita']) ?>"<?= $dis ?>>
          <span class="campo__ayuda">Requerida para Visita agendada. Al guardar una fecha de visita, la solicitud pasa a Visita agendada.</span>
          <?php if (isset($errores['fecha_visita'])): ?>
            <span class="campo__error">! <?= e($errores['fecha_visita']) ?></span>
          <?php endif; ?>
        </div>

        <div class="campo">
          <label class="campo__etiqueta" for="notas_internas">Notas internas</label>
          <textarea class="campo__control" id="notas_internas" name="notas_internas" rows="3"<?= $dis ?>><?= e($datos['notas_internas']) ?></textarea>
          <span class="campo__ayuda">No visibles para el cliente</span>
        </div>
      </section>

      <footer class="drawer-panel__pie">
        <?php if ($solo_lectura): ?>
          <a href="index.php" class="btn btn--secundario">Cerrar</a>
          <a href="editar.php?folio=<?= e($folio_url) ?>" class="btn btn--primario">Editar</a>
        <?php else: ?>
          <a href="index.php" class="btn btn--secundario">Cancelar</a>
          <button type="submit" class="btn btn--primario">Guardar cambios</button>
        <?php endif; ?>
      </footer>
    </form>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
