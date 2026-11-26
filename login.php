<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();

if (usuario_actual() !== null) {
    header('Location: panel.php');
    exit;
}

$error = null;
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    $correo = is_string($_POST['correo'] ?? null) ? trim($_POST['correo']) : '';
    $contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';

    $resultado = intentar_login($correo, $contrasena);
    if ($resultado['ok']) {
        header('Location: panel.php');
        exit;
    }
    // Mismo mensaje para correo inexistente y contraseña errónea (RNF-02).
    $error = $resultado['error'] ?? 'Correo o contraseña incorrectos.';
}

$titulo = 'Iniciar sesión';
$pagina_activa = 'login';
$variante_nav = 'minimal';
$body_class = 'pagina-auth';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<section class="tarjeta-auth">
  <h1 class="tarjeta-auth__titulo">Iniciar sesión</h1>

  <?php if ($error !== null): ?>
    <div class="alerta-flash alerta-flash--error" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php" data-validar="login" novalidate>
    <?= csrf_campo() ?>

    <div class="campo">
      <label class="campo__etiqueta" for="correo">Correo</label>
      <input class="campo__control" type="email" id="correo" name="correo" autocomplete="email"
             value="<?= e($correo) ?>" data-requerido="true" data-tipo="correo">
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="contrasena">Contraseña</label>
      <input class="campo__control" type="password" id="contrasena" name="contrasena"
             autocomplete="current-password" data-requerido="true">
      <button type="button" class="btn btn--enlace btn--pequeno" data-toggle-password="#contrasena">Mostrar</button>
    </div>

    <button type="submit" class="btn btn--primario btn--bloque">Entrar</button>
  </form>

  <p class="tarjeta-auth__pie">¿Aún no tiene cuenta? <a href="registro.php"><strong>Crear cuenta</strong></a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
