<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
$usuario = requerir_cliente();
$id_cliente = (int) $usuario['id_usuario'];

$servicios = listar_servicios(true);

// Con folio se edita una solicitud propia; solo se permite mientras siga Enviada.
$folio = is_string($_REQUEST['folio'] ?? null) ? trim($_REQUEST['folio']) : '';
$solicitud = null;
if ($folio !== '') {
    $solicitud = obtener_solicitud_cliente($id_cliente, $folio);
    if ($solicitud === null || $solicitud['estado'] !== 'Enviada') {
        flash_establecer('error', 'Esta solicitud no existe o ya no se puede modificar.');
        header('Location: panel.php');
        exit;
    }
}

$errores = [];
$datos = [
    'sector' => $solicitud['sector'] ?? '',
    'escala' => $solicitud['escala'] ?? '',
    'id_servicio' => (string) ($solicitud['id_servicio'] ?? ''),
    'descripcion' => $solicitud['descripcion'] ?? '',
    'ubicacion' => $solicitud['ubicacion'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    }

    $resultado = $solicitud === null
        ? crear_solicitud($datos, $id_cliente)
        : editar_solicitud_cliente((int) $solicitud['id_solicitud'], $id_cliente, $datos);
    if ($resultado['ok']) {
        flash_establecer('exito', $solicitud === null
            ? 'Solicitud ' . $resultado['folio'] . ' registrada.'
            : 'Solicitud ' . $solicitud['folio'] . ' actualizada.');
        header('Location: panel.php');
        exit;
    }
    $errores = $resultado['errores'];
}

$tarjetas_sector = [
    'Industrial' => ['Industria', 'Plantas, naves y bodegas'],
    'Comercial' => ['Comercio', 'Locales, tiendas y oficinas'],
    'Residencial' => ['Casa', 'Casas y residencias'],
];
$tarjetas_escala = [
    'Grande' => ['Grande', 'Obra nueva o instalación desde cero'],
    'Mediano' => ['Mediano', 'Revisión, diagnóstico o ampliación'],
    'Pequeño' => ['Pequeño', 'Una reparación puntual, como un contacto o un apagador'],
];

$titulo = $solicitud === null ? 'Nueva solicitud' : 'Editar solicitud';
$pagina_activa = 'panel';
$variante_nav = 'app';
$body_class = 'pagina-app';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<nav class="breadcrumbs" aria-label="Ruta de navegación">
  <a href="panel.php" class="breadcrumbs__enlace">Mis solicitudes</a>
  <span class="breadcrumbs__separador">/</span>
  <span class="breadcrumbs__actual"><?= e($titulo) ?></span>
</nav>

<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo"><?= e($titulo) ?></h1>
    <p class="encabezado-pagina__subtitulo">La primera visita es sin costo y siempre va antes del presupuesto.</p>
  </div>
</div>

<div class="layout-70-30">
  <div class="layout-70-30__principal">
    <form method="post" action="crear.php" data-validar="solicitud" novalidate>
      <?= csrf_campo() ?>
      <?php if ($solicitud !== null): ?>
        <input type="hidden" name="folio" value="<?= e($solicitud['folio']) ?>">
      <?php endif; ?>

      <?php if (isset($errores['solicitud'])): ?>
        <div class="alerta-flash alerta-flash--error" role="alert"><?= e($errores['solicitud']) ?></div>
      <?php endif; ?>

      <h2>1. El trabajo</h2>

      <div class="campo <?= isset($errores['sector']) ? 'campo--error' : '' ?>">
        <span class="campo__etiqueta" id="etiqueta-sector">Tipo de lugar*</span>
        <div class="opciones-grid opciones-grid--3" role="radiogroup" aria-labelledby="etiqueta-sector">
          <?php foreach ($tarjetas_sector as $valor => [$nombre, $ayuda]): ?>
            <label class="tarjeta-opcion <?= $datos['sector'] === $valor ? 'tarjeta-opcion--activa' : '' ?>">
              <input type="radio" name="sector" value="<?= e($valor) ?>" class="tarjeta-opcion__input"
                     <?= $datos['sector'] === $valor ? 'checked' : '' ?>>
              <span class="tarjeta-opcion__titulo"><?= e($nombre) ?></span>
              <span class="tarjeta-opcion__subtexto"><?= e($ayuda) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <?php if (isset($errores['sector'])): ?>
          <span class="campo__error">! <?= e($errores['sector']) ?></span>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['escala']) ? 'campo--error' : '' ?>">
        <span class="campo__etiqueta" id="etiqueta-escala">Tamaño del trabajo*</span>
        <div class="opciones-grid opciones-grid--3" role="radiogroup" aria-labelledby="etiqueta-escala">
          <?php foreach ($tarjetas_escala as $valor => [$nombre, $ayuda]): ?>
            <label class="tarjeta-opcion <?= $datos['escala'] === $valor ? 'tarjeta-opcion--activa' : '' ?>">
              <input type="radio" name="escala" value="<?= e($valor) ?>" class="tarjeta-opcion__input"
                     <?= $datos['escala'] === $valor ? 'checked' : '' ?>>
              <span class="tarjeta-opcion__titulo"><?= e($nombre) ?></span>
              <span class="tarjeta-opcion__subtexto"><?= e($ayuda) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <?php if (isset($errores['escala'])): ?>
          <span class="campo__error">! <?= e($errores['escala']) ?></span>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['id_servicio']) ? 'campo--error' : '' ?>">
        <label class="campo__etiqueta" for="id_servicio">Servicio*</label>
        <select class="campo__control" id="id_servicio" name="id_servicio" data-requerido="true">
          <option value="">Seleccione un servicio</option>
          <?php foreach ($servicios as $servicio): ?>
            <option value="<?= e($servicio['id_servicio']) ?>" <?= (string) $datos['id_servicio'] === (string) $servicio['id_servicio'] ? 'selected' : '' ?>><?= e($servicio['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['id_servicio'])): ?>
          <span class="campo__error">! <?= e($errores['id_servicio']) ?></span>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['descripcion']) ? 'campo--error' : '' ?>">
        <label class="campo__etiqueta" for="descripcion">Descripción*</label>
        <textarea class="campo__control" id="descripcion" name="descripcion" rows="4"
                  placeholder="Ej. Un contacto de la cocina dejó de funcionar." data-requerido="true"><?= e($datos['descripcion']) ?></textarea>
        <?php if (isset($errores['descripcion'])): ?>
          <span class="campo__error">! <?= e($errores['descripcion']) ?></span>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['ubicacion']) ? 'campo--error' : '' ?>">
        <label class="campo__etiqueta" for="ubicacion">Ubicación*</label>
        <input class="campo__control" type="text" id="ubicacion" name="ubicacion" maxlength="200"
               placeholder="Calle, número, colonia" value="<?= e($datos['ubicacion']) ?>" data-requerido="true">
        <?php if (isset($errores['ubicacion'])): ?>
          <span class="campo__error">! <?= e($errores['ubicacion']) ?></span>
        <?php endif; ?>
      </div>

      <p class="campo__ayuda">La visita se coordina con el nombre y teléfono de su cuenta: <?= e($usuario['nombre']) ?>, <?= e(formatear_telefono($usuario['telefono'])) ?>.</p>

      <div class="encabezado-pagina__acciones">
        <a href="panel.php" class="btn btn--secundario">Cancelar</a>
        <button type="submit" class="btn btn--primario"><?= $solicitud === null ? 'Enviar solicitud' : 'Guardar cambios' ?></button>
      </div>
    </form>
  </div>

  <aside class="layout-70-30__lateral">
    <div class="tarjeta-lateral">
      <h3 class="tarjeta-lateral__titulo">Qué sigue</h3>
      <ol>
        <li><strong>Revisión</strong></li>
        <li><strong>Visita sin costo</strong></li>
        <li><strong>Presupuesto</strong></li>
      </ol>
      <p>(664) 000 0000<br>contacto@ejemplo.com</p>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
