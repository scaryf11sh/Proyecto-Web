<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
requerir_admin();

$errores = [];
$datos = ['nombre' => '', 'correo' => '', 'telefono' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    }
    // Las contraseñas no se recortan ni se devuelven al formulario.
    $datos['contrasena'] = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';
    $datos['confirmar_contrasena'] = is_string($_POST['confirmar_contrasena'] ?? null) ? $_POST['confirmar_contrasena'] : '';
    // El aviso de privacidad lo acepta quien se registra; un administrador creado aquí no pasa por ese paso.
    $datos['aviso_privacidad'] = '1';

    $resultado = crear_usuario($datos, 'Administrador');
    if ($resultado['ok']) {
        flash_establecer('exito', 'Administrador ' . $datos['nombre'] . ' creado.');
        header('Location: usuarios.php');
        exit;
    }
    $errores = $resultado['errores'];
}

$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$usuarios = listar_usuarios($q);

$titulo = 'Usuarios';
$pagina_activa = 'usuarios';
$variante_nav = 'app';
$body_class = 'pagina-app';
$ruta_raiz = '';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo">Usuarios</h1>
    <p class="encabezado-pagina__subtitulo">Cuentas registradas y alta de administradores.</p>
  </div>
</div>

<form method="post" action="usuarios.php" data-validar="registro" novalidate>
  <?= csrf_campo() ?>
  <h2>Nuevo administrador</h2>

  <div class="campos-grid campos-grid--2">
    <div class="campo <?= isset($errores['nombre']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="nombre">Nombre completo*</label>
      <input class="campo__control" type="text" id="nombre" name="nombre" maxlength="100"
             autocomplete="off" value="<?= e($datos['nombre']) ?>" data-requerido="true">
      <?php if (isset($errores['nombre'])): ?>
        <span class="campo__error">! <?= e($errores['nombre']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo <?= isset($errores['correo']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="correo">Correo*</label>
      <input class="campo__control" type="email" id="correo" name="correo" maxlength="120"
             autocomplete="off" value="<?= e($datos['correo']) ?>" data-requerido="true" data-tipo="correo">
      <?php if (isset($errores['correo'])): ?>
        <span class="campo__error">! <?= e($errores['correo']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo <?= isset($errores['telefono']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="telefono">Teléfono*</label>
      <input class="campo__control" type="tel" id="telefono" name="telefono" autocomplete="off"
             placeholder="(664) 000 0000" value="<?= e($datos['telefono']) ?>" data-requerido="true" data-tipo="telefono">
      <span class="campo__ayuda">10 dígitos</span>
      <?php if (isset($errores['telefono'])): ?>
        <span class="campo__error">! <?= e($errores['telefono']) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="campos-grid campos-grid--2">
    <div class="campo <?= isset($errores['contrasena']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="contrasena">Contraseña*</label>
      <input class="campo__control" type="password" id="contrasena" name="contrasena"
             autocomplete="new-password" data-requerido="true" data-min-len="8">
      <span class="campo__ayuda">Mínimo 8 caracteres</span>
      <?php if (isset($errores['contrasena'])): ?>
        <span class="campo__error">! <?= e($errores['contrasena']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo <?= isset($errores['confirmar_contrasena']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="confirmar_contrasena">Confirmar contraseña*</label>
      <input class="campo__control" type="password" id="confirmar_contrasena" name="confirmar_contrasena"
             autocomplete="new-password" data-requerido="true" data-coincide="#contrasena">
      <?php if (isset($errores['confirmar_contrasena'])): ?>
        <span class="campo__error">! <?= e($errores['confirmar_contrasena']) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <button type="submit" class="btn btn--primario">Crear administrador</button>
</form>

<form class="barra-filtros" method="get" action="usuarios.php">
  <div class="barra-filtros__busqueda">
    <label class="sr-only" for="buscar-usuarios">Buscar usuarios</label>
    <input class="campo__control" type="search" id="buscar-usuarios" name="q" placeholder="Buscar por nombre o correo" value="<?= e($q) ?>" aria-keyshortcuts="/">
    <kbd class="kbd barra-filtros__atajo" aria-hidden="true">/</kbd>
  </div>
  <div class="barra-filtros__fila">
    <div class="barra-filtros__acciones">
      <button type="submit" class="btn btn--primario btn--pequeno">Filtrar</button>
      <a href="usuarios.php" class="btn btn--enlace">Limpiar filtros</a>
    </div>
  </div>
</form>

<div class="barra-conteo">
  <span><?= e(count($usuarios)) ?> <?= count($usuarios) === 1 ? 'usuario' : 'usuarios' ?></span>
</div>

<?php if ($usuarios === []): ?>
<div class="estado-vacio">
  <p class="estado-vacio__titulo">No se encontraron usuarios</p>
  <p class="estado-vacio__texto">Ajuste la búsqueda o limpie los filtros.</p>
</div>
<?php else: ?>
<div class="tabla-contenedor">
  <table class="tabla-datos tabla-datos--lista">
    <caption>Cuentas registradas</caption>
    <thead>
      <tr>
        <th>Usuario</th>
        <th>Correo</th>
        <th>Teléfono</th>
        <th>Rol</th>
        <th>Solicitudes</th>
        <th>Registro</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $usuario): ?>
      <tr>
        <td data-label="Usuario">
          <?= e($usuario['nombre']) ?>
        </td>
        <td data-label="Correo"><?= e($usuario['correo']) ?></td>
        <td data-label="Teléfono"><?= e(formatear_telefono($usuario['telefono'])) ?></td>
        <td data-label="Rol"><?= e($usuario['rol']) ?></td>
        <td data-label="Solicitudes"><?= e((int)$usuario['total_solicitudes']) ?></td>
        <td data-label="Registro"><?= e(formatear_fecha($usuario['fecha_registro'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php';
