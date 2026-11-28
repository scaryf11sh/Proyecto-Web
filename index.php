<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();

$usuario = usuario_actual();
if ($usuario === null) {
    require __DIR__ . '/inicio.php';
    exit;
}
if ($usuario['rol'] !== 'Administrador') {
    header('Location: panel.php');
    exit;
}

$estados = ['Enviada', 'En revisión', 'Visita agendada', 'Presupuesto', 'Aceptada', 'En progreso', 'Finalizada', 'Rechazada', 'Cancelada'];
$sectores = ['Industrial', 'Comercial', 'Residencial'];

$entrada = static fn(string $clave): string => is_string($_GET[$clave] ?? null) ? trim($_GET[$clave]) : '';
$en_lista = static fn(string $valor, array $permitidos): string => in_array($valor, $permitidos, true) ? $valor : '';

$filtros = [
    'q' => $entrada('q'),
    'estado' => $en_lista($entrada('estado'), $estados),
    'sector' => $en_lista($entrada('sector'), $sectores),
    'desde' => normalizar_fecha_entrada($entrada('desde')) ?? '',
    'hasta' => normalizar_fecha_entrada($entrada('hasta')) ?? '',
];
$pagina = max(1, (int) $entrada('pagina'));

$lista = listar_solicitudes_admin($filtros, $pagina, 10);
$pagina = $lista['pagina'];
$total_paginas = $lista['total_paginas'];

// Los filtros viajan en la querystring de cada enlace de paginación.
$filtros_activos = array_filter($filtros, static fn(string $v): bool => $v !== '');
$url_pagina = static fn(int $p): string => 'index.php?' . http_build_query($filtros_activos + ['pagina' => $p]);

$paginas_visibles = array_unique(array_filter(
    [1, $pagina - 1, $pagina, $pagina + 1, $total_paginas],
    static fn(int $p): bool => $p >= 1 && $p <= $total_paginas
));
sort($paginas_visibles);

$clase_estado = static fn(string $estado): string => str_replace(['é', ' '], ['e', '-'], mb_strtolower($estado));

$titulo = 'Gestión de solicitudes';
$pagina_activa = 'solicitudes';
$variante_nav = 'app';
$body_class = 'pagina-app';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo">Gestión de solicitudes</h1>
    <p class="encabezado-pagina__subtitulo"><?= e(formatear_fecha_encabezado()) ?></p>
  </div>
</div>

<form class="barra-filtros" method="get" action="index.php">
  <div class="barra-filtros__busqueda">
    <input class="campo__control" type="search" id="buscar-solicitudes" name="q" aria-label="Buscar solicitudes" aria-keyshortcuts="/"
           placeholder="Buscar por folio, cliente o teléfono" value="<?= e($filtros['q']) ?>">
    <kbd class="kbd barra-filtros__atajo" aria-hidden="true">/</kbd>
  </div>
  <div class="barra-filtros__fila">
    <div class="campo campo--inline">
      <label class="campo__etiqueta" for="f-estado">Estado</label>
      <select class="campo__control" id="f-estado" name="estado">
        <option value="">Todos</option>
        <?php foreach ($estados as $opcion): ?>
          <option value="<?= e($opcion) ?>" <?= $filtros['estado'] === $opcion ? 'selected' : '' ?>><?= e($opcion) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo campo--inline">
      <label class="campo__etiqueta" for="f-sector">Tipo de lugar</label>
      <select class="campo__control" id="f-sector" name="sector">
        <option value="">Todos</option>
        <?php foreach ($sectores as $opcion): ?>
          <option value="<?= e($opcion) ?>" <?= $filtros['sector'] === $opcion ? 'selected' : '' ?>><?= e($opcion) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo campo--inline">
      <label class="campo__etiqueta" for="f-desde">Desde</label>
      <input class="campo__control" type="date" id="f-desde" name="desde" value="<?= e($filtros['desde']) ?>">
    </div>
    <div class="campo campo--inline">
      <label class="campo__etiqueta" for="f-hasta">Hasta</label>
      <input class="campo__control" type="date" id="f-hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
    </div>
    <div class="barra-filtros__acciones">
      <button type="submit" class="btn btn--primario btn--pequeno">Filtrar</button>
      <a href="index.php" class="btn btn--enlace">Limpiar filtros</a>
    </div>
  </div>
</form>

<div class="barra-conteo">
  <?php if ($lista['total'] > 0): ?>
    <span><?= e($lista['desde_registro']) ?>–<?= e($lista['hasta_registro']) ?> de <?= e($lista['total']) ?> · Orden: prioridad</span>
  <?php else: ?>
    <span>0 solicitudes · Orden: prioridad</span>
  <?php endif; ?>
</div>

<?php if ($lista['items'] === []): ?>
  <div class="estado-vacio">
    <p class="estado-vacio__titulo">No se encontraron solicitudes</p>
    <p class="estado-vacio__texto">Ajuste los filtros de búsqueda.</p>
  </div>
<?php else: ?>
  <div class="tabla-contenedor">
    <table class="tabla-datos tabla-datos--lista">
      <thead>
        <tr>
          <th>Folio</th>
          <th>Cliente</th>
          <th>Lugar · tamaño</th>
          <th>Prioridad</th>
          <th>Servicio</th>
          <th>Estado</th>
          <th>Registro</th>
          <th>Visita</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lista['items'] as $s): ?>
          <?php $folio_url = rawurlencode((string) $s['folio']); ?>
          <tr>
            <td class="texto-mono texto-negrita" data-label="Folio"><?= e($s['folio']) ?></td>
            <td data-label="Cliente"><?= e($s['usuario_nombre']) ?></td>
            <td data-label="Lugar · tamaño"><?= e($s['sector']) ?><span class="tabla-datos__sublinea"><?= e($s['escala']) ?></span></td>
            <td data-label="Prioridad"><span class="badge-prioridad badge-prioridad--<?= e(mb_strtolower(nivel_prioridad((int) $s['prioridad']))) ?>"><?= e(formatear_prioridad((int) $s['prioridad'])) ?></span></td>
            <td data-label="Servicio"><?= e($s['servicio_nombre']) ?></td>
            <td data-label="Estado"><span class="badge-estado badge-estado--<?= e($clase_estado((string) $s['estado'])) ?>"><?= e($s['estado']) ?></span></td>
            <td data-label="Registro"><?= e(formatear_fecha_corta($s['fecha_registro'])) ?></td>
            <td data-label="Visita"><?= e(formatear_fecha_corta($s['fecha_visita'], true)) ?></td>
            <td class="tabla-datos__acciones">
              <a href="editar.php?folio=<?= e($folio_url) ?>&amp;modo=ver">Ver</a> ·
              <a href="editar.php?folio=<?= e($folio_url) ?>">Editar</a> ·
              <a href="eliminar.php?folio=<?= e($folio_url) ?>">Eliminar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="paginacion" aria-label="Paginación de solicitudes">
    <span class="paginacion__info">Página <?= e($pagina) ?> de <?= e($total_paginas) ?></span>
    <div class="paginacion__controles">
      <?php if ($pagina > 1): ?>
        <a href="<?= e($url_pagina($pagina - 1)) ?>" class="paginacion__enlace">Anterior</a>
      <?php else: ?>
        <span class="paginacion__enlace paginacion__enlace--deshabilitado" aria-disabled="true">Anterior</span>
      <?php endif; ?>

      <?php $previa = 0; ?>
      <?php foreach ($paginas_visibles as $p): ?>
        <?php if ($p - $previa > 1): ?>
          <span class="paginacion__elipsis">...</span>
        <?php endif; ?>
        <a href="<?= e($url_pagina($p)) ?>" class="paginacion__enlace <?= $p === $pagina ? 'paginacion__enlace--activo' : '' ?>"<?= $p === $pagina ? ' aria-current="page"' : '' ?>><?= e($p) ?></a>
        <?php $previa = $p; ?>
      <?php endforeach; ?>

      <?php if ($pagina < $total_paginas): ?>
        <a href="<?= e($url_pagina($pagina + 1)) ?>" class="paginacion__enlace">Siguiente</a>
      <?php else: ?>
        <span class="paginacion__enlace paginacion__enlace--deshabilitado" aria-disabled="true">Siguiente</span>
      <?php endif; ?>
    </div>
  </nav>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
