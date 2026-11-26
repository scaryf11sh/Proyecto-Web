<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$servicios = listar_servicios(false);
$servicios_activos = count(array_filter($servicios, static fn(array $s): bool => (int)$s['activo'] === 1));

$titulo = 'Servicios';
$pagina_activa = 'servicios';
$variante_nav = 'app';
$body_class = 'pagina-app';
$ruta_raiz = '../';

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo">Servicios</h1>
    <p class="encabezado-pagina__subtitulo">Solo los servicios activos aparecen en el formulario de nueva solicitud.</p>
  </div>
  <div class="encabezado-pagina__acciones">
    <a href="crear.php" class="btn btn--primario">Nuevo servicio</a>
  </div>
</div>

<div class="barra-conteo">
  <span><?= e(count($servicios)) ?> servicios · <?= e($servicios_activos) ?> activos</span>
</div>

<?php if ($servicios === []): ?>
<div class="estado-vacio">
  <p class="estado-vacio__titulo">No hay servicios registrados</p>
  <p class="estado-vacio__texto">Cree el primer servicio del catálogo.</p>
</div>
<?php else: ?>
<div class="tabla-contenedor">
  <table class="tabla-datos">
    <caption>Servicios del catálogo</caption>
    <thead>
      <tr>
        <th>Servicio</th>
        <th>Descripción</th>
        <th>Solicitudes</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($servicios as $servicio): ?>
      <tr>
        <td class="texto-negrita"><?= e($servicio['nombre']) ?></td>
        <td><?= e($servicio['descripcion'] ?? '') ?></td>
        <td><?= e((int)$servicio['total_solicitudes']) ?></td>
        <td>
          <?php if ((int)$servicio['activo'] === 1): ?>
          <span class="badge-estado badge-estado--aceptada">Activo</span>
          <?php else: ?>
          <span class="badge-estado badge-estado--cancelada">Inactivo</span>
          <?php endif; ?>
        </td>
        <td class="tabla-datos__acciones">
          <a href="editar.php?id=<?= e((int)$servicio['id_servicio']) ?>">Editar</a> ·
          <a href="eliminar.php?id=<?= e((int)$servicio['id_servicio']) ?>">Eliminar</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php';
