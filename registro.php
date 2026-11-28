<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();

if (usuario_actual() !== null) {
    header('Location: panel.php');
    exit;
}

$errores = [];
$datos = [
    'nombre' => '',
    'correo' => '',
    'telefono' => '',
    'aviso_privacidad' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    }
    // Las contraseñas no se recortan ni se devuelven al formulario.
    $datos['contrasena'] = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';
    $datos['confirmar_contrasena'] = is_string($_POST['confirmar_contrasena'] ?? null) ? $_POST['confirmar_contrasena'] : '';

    $resultado = registrar_cliente($datos);
    if ($resultado['ok']) {
        flash_establecer('exito', 'Cuenta creada. Ya puede registrar su primera solicitud.');
        header('Location: panel.php');
        exit;
    }
    $errores = $resultado['errores'];
}

$titulo = 'Crear cuenta';
$pagina_activa = 'registro';
$variante_nav = 'minimal';
$body_class = 'pagina-auth';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<section class="tarjeta-auth">
  <h1 class="tarjeta-auth__titulo">Crear cuenta</h1>

  <form method="post" action="registro.php" data-validar="registro" novalidate>
    <?= csrf_campo() ?>

    <div class="campo <?= isset($errores['nombre']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="nombre">Nombre completo o razón social*</label>
      <input class="campo__control" type="text" id="nombre" name="nombre" maxlength="100"
             autocomplete="name" value="<?= e($datos['nombre']) ?>" data-requerido="true">
      <?php if (isset($errores['nombre'])): ?>
        <span class="campo__error">! <?= e($errores['nombre']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo <?= isset($errores['correo']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="correo">Correo*</label>
      <input class="campo__control" type="email" id="correo" name="correo" maxlength="120"
             autocomplete="email" value="<?= e($datos['correo']) ?>" data-requerido="true" data-tipo="correo">
      <?php if (isset($errores['correo'])): ?>
        <span class="campo__error">! <?= e($errores['correo']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo <?= isset($errores['telefono']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="telefono">Teléfono*</label>
      <input class="campo__control" type="tel" id="telefono" name="telefono" autocomplete="tel"
             placeholder="(664) 000 0000" value="<?= e($datos['telefono']) ?>" data-requerido="true" data-tipo="telefono">
      <span class="campo__ayuda">10 dígitos</span>
      <?php if (isset($errores['telefono'])): ?>
        <span class="campo__error">! <?= e($errores['telefono']) ?></span>
      <?php endif; ?>
    </div>

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

    <div class="campo <?= isset($errores['aviso_privacidad']) ? 'campo--error' : '' ?>">
      <label class="campo__etiqueta" for="aviso_privacidad">
        <input type="checkbox" id="aviso_privacidad" name="aviso_privacidad" value="1"
               <?= $datos['aviso_privacidad'] !== '' ? 'checked' : '' ?> data-requerido="true">
        Acepto el <u>aviso de privacidad</u> *
      </label>
      <?php if (isset($errores['aviso_privacidad'])): ?>
        <span class="campo__error">! <?= e($errores['aviso_privacidad']) ?></span>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn--primario btn--bloque">Crear cuenta</button>
  </form>

  <p class="tarjeta-auth__pie">¿Ya tiene cuenta? <a href="login.php"><strong>Iniciar sesión</strong></a></p>
  <p class="campo__ayuda">* Campos obligatorios</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
