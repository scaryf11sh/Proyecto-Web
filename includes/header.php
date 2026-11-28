<?php
$ruta_raiz = $ruta_raiz ?? (function_exists('prefijo_raiz') ? prefijo_raiz() : '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'OEM Solicitudes') ?> · OEM Solicitudes</title>
<link rel="icon" href="data:,">
<link rel="preload" href="<?= e($ruta_raiz) ?>fonts/onest-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e($ruta_raiz) ?>css/styles.css?v=<?= filemtime(__DIR__ . '/../css/styles.css') ?>">
<script>
document.documentElement.classList.add('js');
</script>
</head>
<body class="<?= e($body_class ?? 'pagina-app') ?>">
