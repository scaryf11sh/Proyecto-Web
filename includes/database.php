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
        } catch (PDOException) {
            throw $primero;
        }
    }

    return $pdo;
}
