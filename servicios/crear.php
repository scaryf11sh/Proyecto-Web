<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$errores = [];
$datos = ['nombre' => '', 'descripcion' => '', 'activo' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $datos = [
        'nombre' => trim(is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : ''),
        'descripcion' => trim(is_string($_POST['descripcion'] ?? null) ? $_POST['descripcion'] : ''),
        'activo' => isset($_POST['activo']) ? 1 : 0,
    ];
    $resultado = crear_servicio($datos);
    if ($resultado['ok']) {
        flash_establecer('exito', 'Servicio creado correctamente.');
        header('Location: index.php');
        exit;
    }
    $errores = $resultado['errores'];
}

$titulo = 'Nuevo servicio';
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
  <span class="breadcrumbs__actual">Nuevo servicio</span>
</nav>

<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo">Nuevo servicio</h1>
    <p class="encabezado-pagina__subtitulo">El nombre debe ser único en el catálogo.</p>
  </div>
</div>

<form method="post" action="crear.php" data-validar="servicio" novalidate>
  <?= csrf_campo() ?>

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
    <button type="submit" class="btn btn--primario">Crear servicio</button>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php';
