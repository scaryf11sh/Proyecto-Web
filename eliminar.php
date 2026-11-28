<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
requerir_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    $solicitud = obtener_solicitud_por_id((int) ($_POST['id_solicitud'] ?? 0));

    if ($solicitud === null) {
        flash_establecer('error', 'No se encontró la solicitud.');
        header('Location: index.php');
        exit;
    }

    $id = (int) $solicitud['id_solicitud'];
    $folio = (string) $solicitud['folio'];
    $volver = 'eliminar.php?folio=' . rawurlencode($folio);

    if ($accion === 'eliminar') {
        if (eliminar_solicitud($id)) {
            flash_establecer('exito', 'Solicitud ' . $folio . ' eliminada.');
            header('Location: index.php');
        } else {
            flash_establecer('error', 'No se pudo eliminar la solicitud.');
            header('Location: ' . $volver);
        }
        exit;
    }

    $nuevos_estados = ['rechazar' => 'Rechazada', 'cancelar' => 'Cancelada'];
    if (!isset($nuevos_estados[$accion])) {
        flash_establecer('error', 'Acción no válida.');
        header('Location: ' . $volver);
        exit;
    }

    $nuevo_estado = $nuevos_estados[$accion];
    $resultado = cambiar_estado($id, $nuevo_estado);
    if ($resultado['ok']) {
        flash_establecer('exito', 'Solicitud ' . $folio . ' cambiada a ' . $nuevo_estado . '.');
        header('Location: index.php');
    } else {
        flash_establecer('error', implode(' ', $resultado['errores']) ?: 'No se pudo cambiar el estado.');
        header('Location: ' . $volver);
    }
    exit;
}

$folio_get = is_string($_GET['folio'] ?? null) ? trim($_GET['folio']) : '';
if ($folio_get !== '') {
    $solicitud = obtener_solicitud_por_folio($folio_get);
} else {
    $id_get = (int) ($_GET['id'] ?? 0);
    $solicitud = $id_get > 0 ? obtener_solicitud_por_id($id_get) : null;
}

if ($solicitud === null) {
    flash_establecer('error', 'No se encontró la solicitud.');
    header('Location: index.php');
    exit;
}

$permitidos = estados_permitidos((string) $solicitud['estado']);

$titulo = 'Eliminar solicitud';
$pagina_activa = 'solicitudes';
$variante_nav = 'app';
$body_class = 'pagina-app';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<div class="dialogo-contenedor">
  <section class="dialogo-tarjeta" role="dialog" aria-labelledby="dialogo-titulo">
    <h1 class="dialogo-tarjeta__titulo" id="dialogo-titulo">Eliminar solicitud</h1>
    <p class="dialogo-tarjeta__destacado"><?= e($solicitud['folio']) ?> · <?= e($solicitud['usuario_nombre']) ?></p>
    <p class="dialogo-tarjeta__texto">
      Esta acción no se puede deshacer. Si la solicitud no procede, conviene cambiar su estado a Rechazada o Cancelada para conservar el registro.
    </p>
    <form method="post" action="eliminar.php">
      <?= csrf_campo() ?>
      <input type="hidden" name="id_solicitud" value="<?= e($solicitud['id_solicitud']) ?>">
      <input type="hidden" name="folio" value="<?= e($solicitud['folio']) ?>">
      <div class="dialogo-tarjeta__acciones">
        <a href="index.php" class="btn btn--secundario">Cancelar</a>
        <?php if (in_array('Rechazada', $permitidos, true)): ?>
          <button type="submit" name="accion" value="rechazar" class="btn btn--secundario">Cambiar a Rechazada</button>
        <?php endif; ?>
        <?php if (in_array('Cancelada', $permitidos, true)): ?>
          <button type="submit" name="accion" value="cancelar" class="btn btn--secundario">Cambiar a Cancelada</button>
        <?php endif; ?>
        <button type="submit" name="accion" value="eliminar" class="btn btn--peligro">Eliminar solicitud</button>
      </div>
    </form>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
