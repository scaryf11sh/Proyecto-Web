<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
$admin = requerir_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $nuevo_rol = is_string($_POST['nuevo_rol'] ?? null) ? $_POST['nuevo_rol'] : '';
    if ($nuevo_rol !== 'Cliente' && $nuevo_rol !== 'Administrador') {
        flash_establecer('error', 'El rol indicado no es válido.');
    } else {
        // cambiar_rol_usuario() impide que el administrador en sesión se quite su propio rol.
        $resultado = cambiar_rol_usuario($id_usuario, $nuevo_rol, (int)$admin['id_usuario']);
        if ($resultado['ok']) {
            flash_establecer('exito', 'Rol actualizado a ' . $nuevo_rol . '.');
        } else {
            flash_establecer('error', $resultado['error'] ?? 'No fue posible cambiar el rol.');
        }
    }
    header('Location: usuarios.php');
    exit;
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
    <p class="encabezado-pagina__subtitulo">Cambio de rol entre Cliente y Administrador.</p>
  </div>
</div>

<form class="barra-filtros" method="get" action="usuarios.php">
  <div class="barra-filtros__busqueda">
    <label class="sr-only" for="buscar-usuarios">Buscar usuarios</label>
    <input class="campo__control" type="text" id="buscar-usuarios" name="q" placeholder="Buscar por nombre, correo o empresa" value="<?= e($q) ?>">
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
  <table class="tabla-datos">
    <caption>Cuentas registradas</caption>
    <thead>
      <tr>
        <th>Usuario</th>
        <th>Correo</th>
        <th>Teléfono</th>
        <th>Rol</th>
        <th>Solicitudes</th>
        <th>Registro</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $usuario): ?>
      <?php $rol_destino = $usuario['rol'] === 'Cliente' ? 'Administrador' : 'Cliente'; ?>
      <tr>
        <td>
          <?= e($usuario['nombre']) ?><?php if (!empty($usuario['empresa'])): ?><span class="tabla-datos__sublinea"><?= e($usuario['empresa']) ?></span><?php endif; ?>
        </td>
        <td><?= e($usuario['correo']) ?></td>
        <td><?= e(formatear_telefono($usuario['telefono'])) ?></td>
        <td><?= e($usuario['rol']) ?></td>
        <td><?= e((int)$usuario['total_solicitudes']) ?></td>
        <td><?= e(formatear_fecha($usuario['fecha_registro'])) ?></td>
        <td>
          <?php if ((int)$usuario['id_usuario'] === (int)$admin['id_usuario']): ?>
          <span class="tabla-datos__sublinea">Sesión actual</span>
          <?php else: ?>
          <form method="post" action="usuarios.php" data-confirmar="¿Cambiar el rol de <?= e($usuario['nombre']) ?> a <?= e($rol_destino) ?>?">
            <?= csrf_campo() ?>
            <input type="hidden" name="id_usuario" value="<?= e((int)$usuario['id_usuario']) ?>">
            <input type="hidden" name="nuevo_rol" value="<?= e($rol_destino) ?>">
            <button type="submit" class="btn btn--secundario btn--pequeno"><?= e($usuario['rol'] === 'Cliente' ? 'Hacer administrador' : 'Hacer cliente') ?></button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php';
