<?php
$variante_nav = $variante_nav ?? 'app';
$pagina_activa = $pagina_activa ?? '';
$ruta_raiz = $ruta_raiz ?? (function_exists('prefijo_raiz') ? prefijo_raiz() : '');
$usuario = function_exists('usuario_actual') ? usuario_actual() : null;
$rol = $usuario['rol'] ?? null;

$enlace_nav = static function (string $href, string $texto, bool $activo) use ($ruta_raiz): void {
    $clase = 'barra-nav__enlace' . ($activo ? ' barra-nav__enlace--activo' : '');
    $actual = $activo ? ' aria-current="page"' : '';
    echo '<a href="' . e($ruta_raiz . $href) . '" class="' . $clase . '"' . $actual . '>' . e($texto) . '</a>';
};
?>
<a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
<header class="barra-nav">
  <div class="barra-nav__interior">
<?php if ($variante_nav === 'minimal'): ?>
    <a href="<?= e($ruta_raiz) ?>index.php" class="barra-nav__marca">OEM Solicitudes</a>
    <a href="<?= e($ruta_raiz) ?>index.php" class="barra-nav__enlace">Volver al inicio</a>
<?php else: ?>
    <a href="<?= e($ruta_raiz) ?><?= $usuario ? 'panel.php' : 'index.php' ?>" class="barra-nav__marca">OEM Solicitudes</a>
    <button type="button" class="barra-nav__toggle" aria-expanded="false" aria-controls="menu-principal" data-toggle-menu>Menú</button>
    <div class="barra-nav__menu" id="menu-principal">
<?php if ($usuario && $variante_nav !== 'publica' && $rol === 'Administrador'): ?>
      <nav class="barra-nav__enlaces" aria-label="Principal">
        <?php $enlace_nav('panel.php', 'Panel', $pagina_activa === 'panel'); ?>
        <?php $enlace_nav('index.php', 'Solicitudes', $pagina_activa === 'solicitudes'); ?>
        <?php $enlace_nav('servicios/index.php', 'Servicios', $pagina_activa === 'servicios'); ?>
        <?php $enlace_nav('reportes.php', 'Reportes', $pagina_activa === 'reportes'); ?>
        <?php $enlace_nav('usuarios.php', 'Usuarios', $pagina_activa === 'usuarios'); ?>
      </nav>
      <div class="barra-nav__acciones">
        <details class="menu-usuario">
          <summary class="menu-usuario__boton"><?= e($usuario['nombre']) ?> ▾</summary>
          <div class="menu-usuario__lista">
            <form method="post" action="<?= e($ruta_raiz) ?>logout.php">
              <?= csrf_campo() ?>
              <button type="submit" class="menu-usuario__item">Cerrar sesión</button>
            </form>
          </div>
        </details>
      </div>
<?php elseif ($usuario && $variante_nav !== 'publica'): ?>
      <nav class="barra-nav__enlaces" aria-label="Principal">
        <?php $enlace_nav('panel.php', 'Mis solicitudes', in_array($pagina_activa, ['panel', 'solicitudes'], true)); ?>
      </nav>
      <div class="barra-nav__acciones">
        <details class="menu-usuario">
          <summary class="menu-usuario__boton"><?= e($usuario['nombre']) ?> ▾</summary>
          <div class="menu-usuario__lista">
            <a href="<?= e($ruta_raiz) ?>crear.php" class="menu-usuario__item">Nueva solicitud</a>
            <form method="post" action="<?= e($ruta_raiz) ?>logout.php">
              <?= csrf_campo() ?>
              <button type="submit" class="menu-usuario__item">Cerrar sesión</button>
            </form>
          </div>
        </details>
      </div>
<?php else: ?>
      <nav class="barra-nav__enlaces" aria-label="Principal">
<?php if ($variante_nav === 'publica'): ?>
        <a href="#como-funciona" class="barra-nav__enlace">Cómo funciona</a>
        <a href="#sectores" class="barra-nav__enlace">Sectores</a>
<?php endif; ?>
      </nav>
      <div class="barra-nav__acciones">
<?php if ($usuario): ?>
        <a href="<?= e($ruta_raiz) ?>panel.php" class="btn btn--primario">Ir a mi panel</a>
<?php else: ?>
        <a href="<?= e($ruta_raiz) ?>login.php" class="barra-nav__enlace<?= $pagina_activa === 'login' ? ' barra-nav__enlace--activo' : '' ?>">Iniciar sesión</a>
        <a href="<?= e($ruta_raiz) ?>registro.php" class="btn btn--primario">Crear cuenta</a>
<?php endif; ?>
      </div>
<?php endif; ?>
    </div>
<?php endif; ?>
  </div>
</header>
<main class="contenedor-principal" id="contenido">
<?php
$flash = function_exists('flash_obtener') ? flash_obtener() : null;
if ($flash):
    $tipo_flash = in_array($flash['tipo'] ?? '', ['exito', 'error', 'aviso', 'info'], true) ? $flash['tipo'] : 'info';
?>
  <div class="alerta-flash alerta-flash--<?= e($tipo_flash) ?>" role="<?= $tipo_flash === 'error' ? 'alert' : 'status' ?>"><?= e($flash['mensaje'] ?? '') ?></div>
<?php endif; ?>
