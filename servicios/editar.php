<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/funciones.php';

iniciar_sesion();
requerir_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $id_servicio = (int)($_POST['id_servicio'] ?? 0);
} else {
    $id_servicio = (int)($_GET['id'] ?? 0);
}

$servicio = obtener_servicio($id_servicio);
if ($servicio === null) {
    flash_establecer('error', 'El servicio no existe.');
    header('Location: index.php');
    exit;
}

$errores = [];
$datos = [
    'nombre' => $servicio['nombre'],
    'descripcion' => $servicio['descripcion'] ?? '',
    'activo' => (int)$servicio['activo'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => trim(is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : ''),
        'descripcion' => trim(is_string($_POST['descripcion'] ?? null) ? $_POST['descripcion'] : ''),
        'activo' => isset($_POST['activo']) ? 1 : 0,
    ];
    $resultado = actualizar_servicio((int)$servicio['id_servicio'], $datos);
    if ($resultado['ok']) {
        flash_establecer('exito', 'Servicio actualizado correctamente.');
        header('Location: index.php');
        exit;
    }
    $errores = $resultado['errores'];
}

$titulo = 'Editar servicio';
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
  <span class="breadcrumbs__actual">Editar servicio</span>
</nav>

<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo">Editar servicio</h1>
    <p class="encabezado-pagina__subtitulo"><?= e($servicio['nombre']) ?></p>
  </div>
</div>

<form method="post" action="editar.php?id=<?= e((int)$servicio['id_servicio']) ?>" data-validar="servicio" novalidate>
  <?= csrf_campo() ?>
  <input type="hidden" name="id_servicio" value="<?= e((int)$servicio['id_servicio']) ?>">

  <?php if (isset($errores['servicio'])): ?>
  <div class="alerta-flash alerta-flash--error" role="alert"><?= e($errores['servicio']) ?></div>
  <?php endif; ?>

  <div class="campo<?= isset($errores['nombre']) ? ' campo--error' : '' ?>">
    <label class="campo__etiqueta" for="nombre">Nombre*</label>
    <input class="campo__control" type="text" id="nombre" name="nombre" maxlength="80"
           value="<?= e($datos['nombre']) ?>" data-requerido="true">
    <?php if (isset($errores['nombre'])): ?>
    <span class="campo__error">! <?= e($errores['nombre']) ?></span>
    <?php endif; ?>
  </div>

  <div class="campo">
    <label class="campo__etiqueta" for="descripcion">Descripción</label>
    <textarea class="campo__control" id="descripcion" name="descripcion" rows="4"><?= e($datos['descripcion']) ?></textarea>
    <span class="campo__ayuda">Opcional. Se muestra como referencia en el formulario de nueva solicitud.</span>
  </div>

  <div class="campo">
    <span class="campo__etiqueta">Estado</span>
    <label for="activo">
      <input type="checkbox" id="activo" name="activo" value="1"<?= (int)$datos['activo'] === 1 ? ' checked' : '' ?>>
      Activo: disponible en el formulario de nueva solicitud.
    </label>
  </div>

  <div class="encabezado-pagina__acciones">
    <a href="index.php" class="btn btn--secundario">Cancelar</a>
    <button type="submit" class="btn btn--primario">Guardar cambios</button>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php';
