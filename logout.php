<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
}
cerrar_sesion();

header('Location: index.php');
exit;
