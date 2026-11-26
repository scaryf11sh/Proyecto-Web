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
