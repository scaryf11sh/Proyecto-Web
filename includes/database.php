<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'oem_solicitudes';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $opciones = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $dsn = 'mysql:host=%s;port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO(sprintf($dsn, DB_HOST), DB_USER, DB_PASS, $opciones);
    } catch (PDOException $primero) {
        // XAMPP a veces solo expone el socket Unix, que PDO usa con host=localhost.
        try {
            $pdo = new PDO(sprintf($dsn, 'localhost'), DB_USER, DB_PASS, $opciones);
        } catch (PDOException $segundo) {
            error_log('MySQL 127.0.0.1: ' . $primero->getMessage());
            error_log('MySQL localhost: ' . $segundo->getMessage());
            $bdFaltante = (int) ($primero->errorInfo[1] ?? 0) === 1049
                || (int) ($segundo->errorInfo[1] ?? 0) === 1049
                || str_contains($primero->getMessage() . $segundo->getMessage(), '[1049]');
            $mensaje = $bdFaltante
                ? 'No se encontró la base de datos oem_solicitudes. Importa sql/oem_solicitudes.sql en MySQL (phpMyAdmin &gt; Importar).'
                : 'No se pudo conectar con MySQL. Verifica que el servicio de MySQL esté iniciado en XAMPP.';
            if (!headers_sent()) {
                http_response_code(503);
            }
            echo <<<HTML
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error de base de datos</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f5f5f5; color: #222; font-family: system-ui, sans-serif; }
        main { max-width: 32rem; margin: 1rem; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, .1); }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; }
        p { margin: 0; line-height: 1.5; }
    </style>
</head>
<body>
    <main>
        <h1>Base de datos no disponible</h1>
        <p>{$mensaje}</p>
    </main>
</body>
</html>
HTML;
            exit;
        }
    }

    return $pdo;
}
