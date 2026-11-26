<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
$usuario = requerir_login();
$es_admin = es_admin();
$rol = $es_admin ? 'Administrador' : 'Cliente';
$destino_cancelar = $es_admin ? 'index.php' : 'panel.php';

$sectores = ['Industrial', 'Comercial', 'Residencial'];
$escalas = ['Grande', 'Mediano', 'Pequeño'];
$canales_admin = ['WhatsApp', 'Llamada', 'Recomendación'];

$servicios = listar_servicios(true);
$clientes = $es_admin ? listar_clientes_activos() : [];

$errores = [];
$datos = [
    'sector' => '',
    'escala' => '',
    'id_servicio' => '',
    'descripcion' => '',
    'ubicacion' => '',
    'nombre_contacto' => $es_admin ? '' : (string) $usuario['nombre'],
    'telefono_contacto' => $es_admin ? '' : formatear_telefono($usuario['telefono']),
    'usar_datos_cuenta' => $es_admin ? '' : '1',
    'canal_origen' => '',
    'id_usuario' => '',
    'sin_cuenta' => '',
    'notas_internas' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
    }
    if (!$es_admin && $datos['usar_datos_cuenta'] !== '') {
        if ($datos['nombre_contacto'] === '') {
            $datos['nombre_contacto'] = (string) $usuario['nombre'];
        }
        if ($datos['telefono_contacto'] === '') {
            $datos['telefono_contacto'] = formatear_telefono($usuario['telefono']);
        }
    }

    $resultado = crear_solicitud(
        array_merge($datos, ['id_servicio' => (int) $datos['id_servicio']]),
        $rol,
        (int) $usuario['id_usuario']
    );
    if ($resultado['ok']) {
        flash_establecer('exito', 'Solicitud ' . $resultado['folio'] . ' registrada.');
        header('Location: ' . $destino_cancelar);
        exit;
    }
    $errores = $resultado['errores'];
}

$texto_prioridad = 'Se calcula con el tamaño y el tipo de lugar.';
if (in_array($datos['sector'], $sectores, true) && in_array($datos['escala'], $escalas, true)) {
    $texto_prioridad = 'Prioridad: ' . formatear_prioridad(calcular_prioridad($datos['sector'], $datos['escala'])) . ' (calculada)';
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

$titulo = 'Nueva solicitud';
$pagina_activa = $es_admin ? 'solicitudes' : 'panel';
$variante_nav = 'app';
$body_class = 'pagina-app';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<nav class="breadcrumbs" aria-label="Ruta de navegación">
  <a href="<?= e($destino_cancelar) ?>" class="breadcrumbs__enlace"><?= $es_admin ? 'Gestión de solicitudes' : 'Mis solicitudes' ?></a>
  <span class="breadcrumbs__separador">/</span>
  <span class="breadcrumbs__actual">Nueva solicitud</span>
</nav>

<div class="encabezado-pagina">
  <div>
    <h1 class="encabezado-pagina__titulo">Nueva solicitud</h1>
    <?php if ($es_admin): ?>
      <p class="encabezado-pagina__subtitulo">Registro de solicitudes recibidas por WhatsApp, llamada o recomendación.</p>
    <?php else: ?>
      <p class="encabezado-pagina__subtitulo">La primera visita es sin costo y siempre va antes del presupuesto.</p>
    <?php endif; ?>
  </div>
</div>

<div class="layout-70-30">
  <div class="layout-70-30__principal">
    <form method="post" action="crear.php" data-validar="solicitud" novalidate>
      <?= csrf_campo() ?>

      <?php if (isset($errores['usuario'])): ?>
        <div class="alerta-flash alerta-flash--error" role="alert"><?= e($errores['usuario']) ?></div>
      <?php endif; ?>

      <?php if ($es_admin): ?>
        <div class="campo <?= isset($errores['canal_origen']) ? 'campo--error' : '' ?>">
          <label class="campo__etiqueta" for="canal_origen">Canal de origen*</label>
          <select class="campo__control" id="canal_origen" name="canal_origen" data-requerido="true">
            <option value="">Seleccione un canal</option>
            <?php foreach ($canales_admin as $canal): ?>
              <option value="<?= e($canal) ?>" <?= $datos['canal_origen'] === $canal ? 'selected' : '' ?>><?= e($canal) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errores['canal_origen'])): ?>
            <span class="campo__error">! <?= e($errores['canal_origen']) ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <h2>1. El trabajo</h2>

      <?php if ($es_admin): ?>
        <div class="campos-grid campos-grid--2">
          <div class="campo <?= isset($errores['sector']) ? 'campo--error' : '' ?>">
            <label class="campo__etiqueta" for="sector">Tipo de lugar*</label>
            <select class="campo__control" id="sector" name="sector" data-requerido="true">
              <option value="">Seleccione</option>
              <?php foreach ($sectores as $sector): ?>
                <option value="<?= e($sector) ?>" <?= $datos['sector'] === $sector ? 'selected' : '' ?>><?= e($sector) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errores['sector'])): ?>
              <span class="campo__error">! <?= e($errores['sector']) ?></span>
            <?php endif; ?>
          </div>
          <div class="campo <?= isset($errores['escala']) ? 'campo--error' : '' ?>">
            <label class="campo__etiqueta" for="escala">Tamaño del trabajo*</label>
            <select class="campo__control" id="escala" name="escala" data-requerido="true">
              <option value="">Seleccione</option>
              <?php foreach ($escalas as $escala): ?>
                <option value="<?= e($escala) ?>" <?= $datos['escala'] === $escala ? 'selected' : '' ?>><?= e($escala) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errores['escala'])): ?>
              <span class="campo__error">! <?= e($errores['escala']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
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
      <?php endif; ?>

      <?php if ($es_admin): ?>
        <div class="caja-prioridad" id="caja-prioridad-calculada">
          <strong class="caja-prioridad__valor" id="texto-prioridad-calculada"><?= e($texto_prioridad) ?></strong>
          <span class="caja-prioridad__ayuda">Se calcula con el tamaño y el tipo de lugar.</span>
        </div>
      <?php endif; ?>

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

      <h2>2. Contacto para la visita</h2>

      <?php if ($es_admin): ?>
        <div class="campo <?= isset($errores['id_usuario']) ? 'campo--error' : '' ?>">
          <label class="campo__etiqueta" for="id_usuario">Cliente con cuenta (opcional)</label>
          <select class="campo__control" id="id_usuario" name="id_usuario" <?= $datos['sin_cuenta'] !== '' ? 'disabled' : '' ?>>
            <option value="">Sin seleccionar</option>
            <?php foreach ($clientes as $cliente): ?>
              <option value="<?= e($cliente['id_usuario']) ?>"
                      data-nombre="<?= e($cliente['nombre']) ?>"
                      data-telefono="<?= e(formatear_telefono($cliente['telefono'])) ?>"
                      <?= (string) $datos['id_usuario'] === (string) $cliente['id_usuario'] ? 'selected' : '' ?>><?= e($cliente['nombre']) ?><?= !empty($cliente['empresa']) ? ' · ' . e($cliente['empresa']) : '' ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errores['id_usuario'])): ?>
            <span class="campo__error">! <?= e($errores['id_usuario']) ?></span>
          <?php endif; ?>
        </div>
        <div class="campo">
          <label class="campo__etiqueta" for="sin-cuenta">
            <input type="checkbox" id="sin-cuenta" name="sin_cuenta" value="1" <?= $datos['sin_cuenta'] !== '' ? 'checked' : '' ?>>
            Sin cuenta vinculada
          </label>
        </div>
      <?php else: ?>
        <div class="campo">
          <label class="campo__etiqueta" for="usar-datos-cuenta">
            <input type="checkbox" id="usar-datos-cuenta" name="usar_datos_cuenta" value="1"
                   data-nombre="<?= e($usuario['nombre']) ?>"
                   data-telefono="<?= e(formatear_telefono($usuario['telefono'])) ?>"
                   <?= $datos['usar_datos_cuenta'] !== '' ? 'checked' : '' ?>>
            Usar los datos de la cuenta
          </label>
        </div>
      <?php endif; ?>

      <div class="campos-grid campos-grid--2">
        <div class="campo <?= isset($errores['nombre_contacto']) ? 'campo--error' : '' ?>">
          <label class="campo__etiqueta" for="nombre_contacto"><?= $es_admin ? 'Nombre del contacto*' : 'Nombre*' ?></label>
          <input class="campo__control" type="text" id="nombre_contacto" name="nombre_contacto" maxlength="100"
                 value="<?= e($datos['nombre_contacto']) ?>" data-requerido="true">
          <?php if (isset($errores['nombre_contacto'])): ?>
            <span class="campo__error">! <?= e($errores['nombre_contacto']) ?></span>
          <?php endif; ?>
        </div>
        <div class="campo <?= isset($errores['telefono_contacto']) ? 'campo--error' : '' ?>">
          <label class="campo__etiqueta" for="telefono_contacto"><?= $es_admin ? 'Teléfono de contacto*' : 'Teléfono*' ?></label>
          <input class="campo__control" type="tel" id="telefono_contacto" name="telefono_contacto"
                 placeholder="(664) 000 0000" value="<?= e($datos['telefono_contacto']) ?>" data-requerido="true" data-tipo="telefono">
          <span class="campo__ayuda">10 dígitos</span>
          <?php if (isset($errores['telefono_contacto'])): ?>
            <span class="campo__error">! <?= e($errores['telefono_contacto']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($es_admin): ?>
        <div class="campo">
          <label class="campo__etiqueta" for="notas_internas">Notas internas (opcional)</label>
          <textarea class="campo__control" id="notas_internas" name="notas_internas" rows="3"><?= e($datos['notas_internas']) ?></textarea>
          <span class="campo__ayuda">No visibles para el cliente</span>
        </div>
      <?php endif; ?>

      <div class="encabezado-pagina__acciones">
        <a href="<?= e($destino_cancelar) ?>" class="btn btn--secundario">Cancelar</a>
        <button type="submit" class="btn btn--primario"><?= $es_admin ? 'Guardar solicitud' : 'Enviar solicitud' ?></button>
      </div>
    </form>
  </div>

  <?php if (!$es_admin): ?>
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
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
