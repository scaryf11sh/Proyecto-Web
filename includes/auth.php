<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function iniciar_sesion(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function usuario_actual(): ?array
{
    iniciar_sesion();
    $usuario = $_SESSION['usuario'] ?? null;
    return is_array($usuario) ? $usuario : null;
}

function es_admin(): bool
{
    $usuario = usuario_actual();
    return $usuario !== null && $usuario['rol'] === 'Administrador';
}

function es_cliente(): bool
{
    $usuario = usuario_actual();
    return $usuario !== null && $usuario['rol'] === 'Cliente';
}

function prefijo_raiz(): string
{
    $script = (string) ($_SERVER['SCRIPT_FILENAME'] ?? '');
    if ($script === '') {
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? ''));
    }
    return basename(dirname($script)) === 'servicios' ? '../' : '';
}

/**
 * Las rutas relativas se resuelven contra la raíz de la app, para que funcionen
 * igual desde index.php y desde servicios/*.
 */
function auth_ruta(string $ruta): string
{
    if (preg_match('#^(/|https?://|\.\./)#', $ruta) === 1) {
        return $ruta;
    }
    return prefijo_raiz() . $ruta;
}

function auth_redirigir(string $ruta): never
{
    header('Location: ' . auth_ruta($ruta));
    exit;
}

function requerir_login(string $ruta_login = 'login.php'): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        auth_redirigir($ruta_login);
    }
    return $usuario;
}

function requerir_admin(string $ruta_login = 'login.php', string $ruta_panel_cliente = 'panel.php'): array
{
    $usuario = requerir_login($ruta_login);
    if ($usuario['rol'] !== 'Administrador') {
        auth_redirigir($ruta_panel_cliente);
    }
    return $usuario;
}

function guardar_sesion_usuario(array $fila): array
{
    $usuario = [
        'id_usuario' => (int) $fila['id_usuario'],
        'nombre' => (string) $fila['nombre'],
        'empresa' => $fila['empresa'] !== null ? (string) $fila['empresa'] : null,
        'correo' => (string) $fila['correo'],
        'telefono' => (string) $fila['telefono'],
        'rol' => (string) $fila['rol'],
    ];
    // Un id de sesión nuevo evita la fijación de sesión; el token CSRF también se renueva.
    session_regenerate_id(true);
    $_SESSION['usuario'] = $usuario;
    unset($_SESSION['csrf_token']);
    csrf_token();
    return $usuario;
}

function intentar_login(string $correo, string $contrasena): array
{
    iniciar_sesion();
    $fallo = ['ok' => false, 'error' => 'Correo o contraseña incorrectos.', 'usuario' => null];

    $stmt = db()->prepare(
        'SELECT id_usuario, nombre, empresa, correo, telefono, contrasena, rol
         FROM usuario WHERE correo = :correo LIMIT 1'
    );
    $stmt->execute([':correo' => trim($correo)]);
    $fila = $stmt->fetch();

    // Con correo inexistente se verifica contra un hash de relleno para no revelar su existencia por tiempo.
    $hash = $fila['contrasena'] ?? '$2y$12$yI/7XfRJwmpXt0mkvRTZdeA2xXeJPAjLLMYHRE2bzNRb06XPdWwzW';
    $valida = password_verify($contrasena, $hash);
    if (!$fila || !$valida) {
        return $fallo;
    }

    $usuario = guardar_sesion_usuario($fila);
    return ['ok' => true, 'error' => null, 'usuario' => $usuario];
}

function registrar_cliente(array $datos): array
{
    iniciar_sesion();
    $errores = validar_registro($datos);
    if ($errores !== []) {
        return ['ok' => false, 'errores' => $errores, 'id_usuario' => null];
    }

    $empresa = trim((string) ($datos['empresa'] ?? ''));
    $fila = [
        'nombre' => trim((string) $datos['nombre']),
        'empresa' => $empresa !== '' ? $empresa : null,
        'correo' => trim((string) $datos['correo']),
        'telefono' => limpiar_telefono((string) $datos['telefono']),
        'rol' => 'Cliente',
    ];

    try {
        $stmt = db()->prepare(
            'INSERT INTO usuario (nombre, empresa, correo, telefono, contrasena, rol)
             VALUES (:nombre, :empresa, :correo, :telefono, :contrasena, :rol)'
        );
        $stmt->execute([
            ':nombre' => $fila['nombre'],
            ':empresa' => $fila['empresa'],
            ':correo' => $fila['correo'],
            ':telefono' => $fila['telefono'],
            ':contrasena' => password_hash((string) $datos['contrasena'], PASSWORD_BCRYPT),
            ':rol' => $fila['rol'],
        ]);
    } catch (PDOException $ex) {
        // Registro simultáneo con el mismo correo: gana el índice único.
        if ($ex->getCode() === '23000') {
            return ['ok' => false, 'errores' => ['correo' => 'Ya existe una cuenta con este correo.'], 'id_usuario' => null];
        }
        throw $ex;
    }

    $fila['id_usuario'] = (int) db()->lastInsertId();
    guardar_sesion_usuario($fila);
    return ['ok' => true, 'errores' => [], 'id_usuario' => $fila['id_usuario']];
}

function cerrar_sesion(): void
{
    iniciar_sesion();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $p['path'],
            'domain' => $p['domain'],
            'secure' => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

function csrf_token(): string
{
    iniciar_sesion();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
}

function verificar_csrf(?string $token = null): bool
{
    iniciar_sesion();
    $token ??= (string) ($_POST['csrf_token'] ?? '');
    $esperado = $_SESSION['csrf_token'] ?? '';
    return is_string($esperado) && $esperado !== '' && hash_equals($esperado, $token);
}

function exigir_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && verificar_csrf()) {
        return;
    }
    http_response_code(403);
    flash_establecer('error', 'La solicitud no es válida o la sesión expiró. Intente de nuevo.');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Solicitud no válida (403). Regrese e intente de nuevo.';
    exit;
}

function flash_establecer(string $tipo, string $mensaje): void
{
    iniciar_sesion();
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_obtener(): ?array
{
    iniciar_sesion();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}
