<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/funciones.php';

iniciar_sesion();
requerir_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $servicio = obtener_servicio((int)($_POST['id_servicio'] ?? 0));
    if ($servicio === null) {
        flash_establecer('error', 'El servicio no existe.');
    } else {
        // Con solicitudes asociadas, eliminar_servicio() desactiva en lugar de borrar.
        $resultado = eliminar_servicio((int)$servicio['id_servicio']);
        if ($resultado['ok']) {
            flash_establecer($resultado['resultado'] === 'desactivado' ? 'aviso' : 'exito', $resultado['mensaje']);
        } else {
            flash_establecer('error', 'No fue posible eliminar el servicio.');
        }
    }
    header('Location: index.php');
    exit;
}

$servicio = obtener_servicio((int)($_GET['id'] ?? 0));
if ($servicio === null) {
    flash_establecer('error', 'El servicio no existe.');
    header('Location: index.php');
    exit;
}

$titulo = 'Eliminar servicio';
$pagina_activa = 'servicios';
$variante_nav = 'app';
$body_class = 'pagina-app';
$ruta_raiz = '../';

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<nav class="breadcrumbs" aria-label="Ruta de navegación">
  <a href="index.php" class="breadcrumbs__enlace">Servicios</a>
  <span class="breadcrumbs__separador">/</span>
  <span class="breadcrumbs__actual">Eliminar servicio</span>
</nav>

<div class="dialogo-contenedor">
  <section class="dialogo-tarjeta" role="dialog" aria-labelledby="dialogo-titulo">
    <h1 class="dialogo-tarjeta__titulo" id="dialogo-titulo">Eliminar servicio</h1>
    <p class="dialogo-tarjeta__destacado"><?= e($servicio['nombre']) ?></p>
    <p class="dialogo-tarjeta__texto">
      <?php if ((int)$servicio['total_solicitudes'] > 0): ?>
      Este servicio tiene <?= e((int)$servicio['total_solicitudes']) ?> solicitudes asociadas.
      En lugar de eliminarse se desactivará para conservar el historial; puede reactivarlo después desde Editar.
      <?php else: ?>
      Esta acción no se puede deshacer.
      <?php endif; ?>
    </p>
    <div class="dialogo-tarjeta__acciones">
      <a href="index.php" class="btn btn--secundario">Cancelar</a>
      <form method="post" action="eliminar.php">
        <?= csrf_campo() ?>
        <input type="hidden" name="id_servicio" value="<?= e((int)$servicio['id_servicio']) ?>">
        <button type="submit" class="btn btn--peligro">
          <?= (int)$servicio['total_solicitudes'] > 0 ? 'Desactivar servicio' : 'Eliminar servicio' ?>
        </button>
      </form>
    </div>
  </section>
</div>
<?php require __DIR__ . '/../includes/footer.php';
