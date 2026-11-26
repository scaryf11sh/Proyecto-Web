<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

const SECTORES = ['Industrial', 'Comercial', 'Residencial'];
const ESCALAS = ['Grande', 'Mediano', 'Pequeño'];
const CANALES = ['Web', 'WhatsApp', 'Llamada', 'Recomendación'];
const CANALES_ADMIN = ['WhatsApp', 'Llamada', 'Recomendación'];
const ESTADOS = ['Enviada', 'En revisión', 'Visita agendada', 'Presupuesto', 'Aceptada', 'En progreso', 'Finalizada', 'Rechazada', 'Cancelada'];
const ESTADOS_CERRADOS = ['Finalizada', 'Rechazada', 'Cancelada'];
const DIAS_SEMANA = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const MESES_ES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const FECHA_REFERENCIA_BASE = '2026-10-25';

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function limpiar_telefono(string $telefono): string
{
    return preg_replace('/\D+/', '', $telefono) ?? '';
}

function formatear_telefono(?string $telefono): string
{
    $digitos = limpiar_telefono((string) $telefono);
    if ($digitos === '') {
        return '—';
    }
    if (strlen($digitos) !== 10) {
        return $digitos;
    }
    return sprintf('(%s) %s %s', substr($digitos, 0, 3), substr($digitos, 3, 3), substr($digitos, 6));
}

function parsear_fecha_sql(?string $fecha): ?DateTimeImmutable
{
    $fecha = trim((string) $fecha);
    if ($fecha === '' || str_starts_with($fecha, '0000-00-00')) {
        return null;
    }
    try {
        return new DateTimeImmutable($fecha);
    } catch (Exception) {
        return null;
    }
}

/**
 * La fecha base coincide con los datos semilla; si hay solicitudes más recientes,
 * el sistema avanza con ellas para que "hoy" y los KPI no queden en el pasado.
 */
function fecha_referencia_sistema(): string
{
    static $referencia = null;
    if ($referencia !== null) {
        return $referencia;
    }
    $max = db()->query('SELECT DATE(MAX(fecha_registro)) FROM solicitud')->fetchColumn();
    $referencia = ($max !== false && $max !== null && (string) $max > FECHA_REFERENCIA_BASE) ? (string) $max : FECHA_REFERENCIA_BASE;
    return $referencia;
}

function formatear_fecha(?string $fecha, bool $con_hora = false): string
{
    $f = parsear_fecha_sql($fecha);
    if ($f === null) {
        return '—';
    }
    return $f->format($con_hora ? 'd/m/Y H:i' : 'd/m/Y');
}

function formatear_fecha_corta(?string $fecha, bool $con_hora = false): string
{
    $f = parsear_fecha_sql($fecha);
    if ($f === null) {
        return '—';
    }
    return $f->format($con_hora ? 'd/m H:i' : 'd/m');
}

function formatear_fecha_visita_larga(?string $fecha_visita): string
{
    $f = parsear_fecha_sql($fecha_visita);
    if ($f === null) {
        return '—';
    }
    return DIAS_SEMANA[(int) $f->format('w')] . ' ' . $f->format('d/m/Y, H:i');
}

function formatear_fecha_encabezado(?string $fecha = null): string
{
    $f = parsear_fecha_sql($fecha ?? fecha_referencia_sistema());
    if ($f === null) {
        return '—';
    }
    return ucfirst(DIAS_SEMANA[(int) $f->format('w')]) . ' ' . $f->format('d/m/Y');
}

function formatear_fecha_relativa(string $fecha_registro, ?string $fecha_referencia = null): string
{
    $registro = parsear_fecha_sql($fecha_registro);
    $referencia = parsear_fecha_sql($fecha_referencia ?? fecha_referencia_sistema());
    if ($registro === null || $referencia === null) {
        return '—';
    }
    $dias = (int) $registro->setTime(0, 0)->diff($referencia->setTime(0, 0))->format('%r%a');
    if ($dias <= 0) {
        return 'hoy';
    }
    return $dias === 1 ? 'hace 1 día' : "hace {$dias} días";
}

function normalizar_fecha_entrada(?string $valor, bool $con_hora = false): ?string
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::\d{2})?)?$/', $valor, $m) === 1) {
        [$anio, $mes, $dia] = [(int) $m[1], (int) $m[2], (int) $m[3]];
    } elseif (preg_match('#^(\d{2})/(\d{2})/(\d{4})(?:[T ](\d{2}):(\d{2}))?$#', $valor, $m) === 1) {
        [$dia, $mes, $anio] = [(int) $m[1], (int) $m[2], (int) $m[3]];
    } else {
        return null;
    }

    $hora = isset($m[4]) && $m[4] !== '' ? (int) $m[4] : 0;
    $minuto = isset($m[5]) && $m[5] !== '' ? (int) $m[5] : 0;
    if (!checkdate($mes, $dia, $anio) || $hora > 23 || $minuto > 59) {
        return null;
    }

    $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    return $con_hora ? sprintf('%s %02d:%02d:00', $fecha, $hora, $minuto) : $fecha;
}

function calcular_prioridad(string $sector, string $escala): int
{
    $matriz = [
        'Industrial' => ['Grande' => 9, 'Mediano' => 8, 'Pequeño' => 3],
        'Comercial' => ['Grande' => 7, 'Mediano' => 6, 'Pequeño' => 2],
        'Residencial' => ['Grande' => 5, 'Mediano' => 4, 'Pequeño' => 1],
    ];
    if (!isset($matriz[$sector][$escala])) {
        throw new InvalidArgumentException("Sector o escala fuera de catálogo: {$sector} / {$escala}");
    }
    return $matriz[$sector][$escala];
}

function nivel_prioridad(int $prioridad): string
{
    if ($prioridad >= 7) {
        return 'Alta';
    }
    return $prioridad >= 4 ? 'Media' : 'Baja';
}

function formatear_prioridad(int $prioridad): string
{
    return $prioridad . ' · ' . nivel_prioridad($prioridad);
}

function generar_folio(PDO $pdo, ?int $anio_dos_digitos = null): string
{
    $transaccion_propia = !$pdo->inTransaction();
    if ($transaccion_propia) {
        $pdo->beginTransaction();
    }
    try {
        $anio = sprintf('%02d', $anio_dos_digitos ?? (int) date('y'));
        $prefijo = "OEM-{$anio}-";

        $stmt = $pdo->prepare('SELECT folio FROM solicitud WHERE folio LIKE :prefijo ORDER BY folio DESC LIMIT 1 FOR UPDATE');
        $stmt->execute([':prefijo' => $prefijo . '%']);
        $ultimo = $stmt->fetchColumn();
        $siguiente = $ultimo === false ? 1 : ((int) substr((string) $ultimo, -4)) + 1;

        if ($transaccion_propia) {
            $pdo->commit();
        }
        return $prefijo . str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
    } catch (Throwable $ex) {
        if ($transaccion_propia && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
}

function etapas_seguimiento(): array
{
    return ['Enviada', 'En revisión', 'Visita agendada', 'Presupuesto', 'Aceptada', 'En progreso', 'Finalizada'];
}

function estados_permitidos(string $actual, string $rol = 'Administrador'): array
{
    if ($rol === 'Cliente') {
        return es_cancelable_por_cliente($actual) ? ['Cancelada'] : [];
    }
    if ($rol !== 'Administrador') {
        return [];
    }
    $transiciones = [
        'Enviada' => ['En revisión', 'Cancelada'],
        'En revisión' => ['Visita agendada', 'Rechazada', 'Cancelada'],
        'Visita agendada' => ['Presupuesto', 'Rechazada', 'Cancelada'],
        'Presupuesto' => ['Aceptada', 'Rechazada', 'Cancelada'],
        'Aceptada' => ['En progreso', 'Cancelada'],
        'En progreso' => ['Finalizada', 'Cancelada'],
        'Rechazada' => ['En revisión'],
        'Finalizada' => [],
        'Cancelada' => [],
    ];
    return $transiciones[$actual] ?? [];
}

function es_cancelable_por_cliente(string $estado): bool
{
    return $estado === 'Enviada' || $estado === 'En revisión';
}

function cambiar_estado(
    int $id_solicitud,
    string $nuevo_estado,
    string $rol,
    ?string $fecha_visita = null,
    ?int $id_usuario_cliente = null
): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id_usuario, estado, fecha_visita FROM solicitud WHERE id_solicitud = :id FOR UPDATE');
        $stmt->execute([':id' => $id_solicitud]);
        $fila = $stmt->fetch();

        $errores = [];
        if (!$fila) {
            $errores['solicitud'] = 'La solicitud no existe.';
        } elseif ($rol === 'Cliente' && ($id_usuario_cliente === null || (int) $fila['id_usuario'] !== $id_usuario_cliente)) {
            // Se responde igual que si no existiera para no filtrar solicitudes ajenas.
            $errores['solicitud'] = 'La solicitud no existe.';
        } elseif (!in_array($nuevo_estado, estados_permitidos((string) $fila['estado'], $rol), true)) {
            $errores['estado'] = "No se puede cambiar de {$fila['estado']} a {$nuevo_estado}.";
        }

        $visita_cruda = trim((string) $fecha_visita);
        $visita = normalizar_fecha_entrada($visita_cruda, true);
        if ($errores === []) {
            if ($visita_cruda !== '' && $visita === null) {
                $errores['fecha_visita'] = 'Ingrese una fecha y hora válidas.';
            } elseif ($nuevo_estado === 'Visita agendada' && $visita === null && ($fecha_visita !== null || $fila['fecha_visita'] === null)) {
                $errores['fecha_visita'] = 'Requerida para Visita agendada';
            }
        }

        if ($errores !== []) {
            $pdo->rollBack();
            return ['ok' => false, 'errores' => $errores];
        }

        $upd = $pdo->prepare('UPDATE solicitud SET estado = :estado, fecha_visita = :visita WHERE id_solicitud = :id');
        $upd->execute([
            ':estado' => $nuevo_estado,
            ':visita' => $visita ?? $fila['fecha_visita'],
            ':id' => $id_solicitud,
        ]);
        $pdo->commit();
        return ['ok' => true, 'errores' => []];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
}

function validar_registro(array $datos): array
{
    $errores = [];
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $empresa = trim((string) ($datos['empresa'] ?? ''));
    $correo = trim((string) ($datos['correo'] ?? ''));
    $contrasena = (string) ($datos['contrasena'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $errores['nombre'] = 'El nombre completo o razón social es obligatorio.';
    }
    if (mb_strlen($empresa) > 100) {
        $errores['empresa'] = 'El nombre de la empresa no puede exceder 100 caracteres.';
    }
    if ($correo === '' || mb_strlen($correo) > 120 || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
        $errores['correo'] = 'Ingrese un correo electrónico válido.';
    } else {
        $stmt = db()->prepare('SELECT 1 FROM usuario WHERE correo = :correo LIMIT 1');
        $stmt->execute([':correo' => $correo]);
        if ($stmt->fetchColumn() !== false) {
            $errores['correo'] = 'Ya existe una cuenta con este correo.';
        }
    }
    if (strlen(limpiar_telefono((string) ($datos['telefono'] ?? ''))) !== 10) {
        $errores['telefono'] = 'Ingrese un teléfono válido de 10 dígitos.';
    }
    if (mb_strlen($contrasena) < 8) {
        $errores['contrasena'] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!hash_equals($contrasena, (string) ($datos['confirmar_contrasena'] ?? ''))) {
        $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden.';
    }
    if (empty($datos['aviso_privacidad'])) {
        $errores['aviso_privacidad'] = 'Debe aceptar el aviso de privacidad.';
    }
    return $errores;
}

function validar_solicitud(array $datos, string $modo = 'crear_cliente', ?string $estado_actual = null): array
{
    $errores = [];
    $crea = $modo === 'crear_cliente' || $modo === 'crear_admin';

    if ($modo === 'crear_admin' && !in_array((string) ($datos['canal_origen'] ?? ''), CANALES_ADMIN, true)) {
        $errores['canal_origen'] = 'Seleccione un canal de origen válido.';
    }
    if (!in_array((string) ($datos['sector'] ?? ''), SECTORES, true)) {
        $errores['sector'] = 'Seleccione el tipo de lugar.';
    }
    if (!in_array((string) ($datos['escala'] ?? ''), ESCALAS, true)) {
        $errores['escala'] = 'Seleccione el tamaño del trabajo.';
    }

    $id_servicio = (int) ($datos['id_servicio'] ?? 0);
    $stmt = db()->prepare('SELECT activo FROM servicio WHERE id_servicio = :id');
    $stmt->execute([':id' => $id_servicio]);
    $activo = $stmt->fetchColumn();
    if ($activo === false) {
        $errores['id_servicio'] = 'Seleccione un servicio válido.';
    } elseif ($crea && (int) $activo !== 1) {
        $errores['id_servicio'] = 'El servicio seleccionado no está disponible.';
    }

    if (trim((string) ($datos['descripcion'] ?? '')) === '') {
        $errores['descripcion'] = 'La descripción es obligatoria.';
    }
    $ubicacion = trim((string) ($datos['ubicacion'] ?? ''));
    if ($ubicacion === '' || mb_strlen($ubicacion) > 200) {
        $errores['ubicacion'] = 'La ubicación es obligatoria (máximo 200 caracteres).';
    }
    $contacto = trim((string) ($datos['nombre_contacto'] ?? ''));
    if ($contacto === '' || mb_strlen($contacto) > 100) {
        $errores['nombre_contacto'] = 'El nombre de contacto es obligatorio (máximo 100 caracteres).';
    }
    if (strlen(limpiar_telefono((string) ($datos['telefono_contacto'] ?? ''))) !== 10) {
        $errores['telefono_contacto'] = 'Ingrese un teléfono válido de 10 dígitos.';
    }

    if ($modo === 'editar_admin') {
        $estado = (string) ($datos['estado'] ?? $estado_actual ?? '');
        if (!in_array($estado, ESTADOS, true)) {
            $errores['estado'] = 'Seleccione un estado válido.';
        } elseif ($estado_actual !== null && $estado !== $estado_actual
            && !in_array($estado, estados_permitidos($estado_actual, 'Administrador'), true)) {
            $errores['estado'] = "No se puede cambiar de {$estado_actual} a {$estado}.";
        }

        $visita_cruda = trim((string) ($datos['fecha_visita'] ?? ''));
        $visita = normalizar_fecha_entrada($visita_cruda, true);
        if ($estado === 'Visita agendada' && $visita === null) {
            $errores['fecha_visita'] = 'Requerida para Visita agendada';
        } elseif ($visita_cruda !== '' && $visita === null) {
            $errores['fecha_visita'] = 'Ingrese una fecha y hora válidas.';
        }
    }
    return $errores;
}

function validar_servicio(array $datos, ?int $id_servicio_actual = null): array
{
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    if ($nombre === '') {
        return ['nombre' => 'El nombre del servicio es obligatorio.'];
    }
    if (mb_strlen($nombre) > 80) {
        return ['nombre' => 'El nombre no puede exceder 80 caracteres.'];
    }
    $stmt = db()->prepare('SELECT 1 FROM servicio WHERE nombre = :nombre AND id_servicio <> :id LIMIT 1');
    $stmt->execute([':nombre' => $nombre, ':id' => $id_servicio_actual ?? 0]);
    if ($stmt->fetchColumn() !== false) {
        return ['nombre' => 'Ya existe un servicio con este nombre.'];
    }
    return [];
}

function sql_base_solicitud(bool $con_notas = true): string
{
    return 'SELECT s.id_solicitud, s.folio, s.id_usuario, s.nombre_contacto, s.telefono_contacto, s.sector, s.escala,
                s.id_servicio, sv.nombre AS servicio_nombre, s.descripcion, s.ubicacion, s.canal_origen, s.prioridad,
                s.estado, s.fecha_visita, '
        . ($con_notas ? 's.notas_internas, ' : '')
        . 's.fecha_registro, s.fecha_actualizacion,
                u.nombre AS usuario_nombre, u.empresa AS usuario_empresa, u.correo AS usuario_correo,
                COALESCE(NULLIF(u.empresa, \'\'), u.nombre, s.nombre_contacto) AS cliente_mostrar
         FROM solicitud s
         JOIN servicio sv ON sv.id_servicio = s.id_servicio
         LEFT JOIN usuario u ON u.id_usuario = s.id_usuario';
}

function normalizar_fila_solicitud(array $fila): array
{
    $fila['id_solicitud'] = (int) $fila['id_solicitud'];
    $fila['id_usuario'] = $fila['id_usuario'] !== null ? (int) $fila['id_usuario'] : null;
    $fila['id_servicio'] = (int) $fila['id_servicio'];
    $fila['prioridad'] = (int) $fila['prioridad'];
    return $fila;
}

function obtener_solicitud_por_folio(string $folio): ?array
{
    $stmt = db()->prepare(sql_base_solicitud() . ' WHERE s.folio = :folio LIMIT 1');
    $stmt->execute([':folio' => trim($folio)]);
    $fila = $stmt->fetch();
    return $fila ? normalizar_fila_solicitud($fila) : null;
}

function obtener_solicitud_por_id(int $id_solicitud): ?array
{
    $stmt = db()->prepare(sql_base_solicitud() . ' WHERE s.id_solicitud = :id LIMIT 1');
    $stmt->execute([':id' => $id_solicitud]);
    $fila = $stmt->fetch();
    return $fila ? normalizar_fila_solicitud($fila) : null;
}

function texto_o_null(mixed $valor): ?string
{
    $valor = trim((string) $valor);
    return $valor === '' ? null : $valor;
}

function crear_solicitud(array $datos, string $rol_creador, ?int $id_usuario_sesion = null): array
{
    $fallo = static fn(array $errores): array => ['ok' => false, 'errores' => $errores, 'folio' => null, 'id_solicitud' => null];

    if ($rol_creador === 'Cliente') {
        $modo = 'crear_cliente';
        $cuenta = $id_usuario_sesion !== null ? obtener_usuario($id_usuario_sesion) : null;
        if ($cuenta === null) {
            return $fallo(['usuario' => 'Debe iniciar sesión para crear una solicitud.']);
        }
        // Con "usar datos de la cuenta" o sin contacto escrito se toman los datos de la cuenta, no del formulario.
        if (!empty($datos['usar_datos_cuenta']) || trim((string) ($datos['nombre_contacto'] ?? '')) === '') {
            $datos['nombre_contacto'] = $cuenta['nombre'];
        }
        if (!empty($datos['usar_datos_cuenta']) || trim((string) ($datos['telefono_contacto'] ?? '')) === '') {
            $datos['telefono_contacto'] = $cuenta['telefono'];
        }
        $canal = 'Web';
        $id_usuario = $id_usuario_sesion;
        $notas = null;
    } elseif ($rol_creador === 'Administrador') {
        $modo = 'crear_admin';
        $canal = (string) ($datos['canal_origen'] ?? '');
        $id_usuario = (!empty($datos['id_usuario']) && empty($datos['sin_cuenta'])) ? (int) $datos['id_usuario'] : null;
        $notas = texto_o_null($datos['notas_internas'] ?? '');
    } else {
        return $fallo(['usuario' => 'Rol no autorizado para crear solicitudes.']);
    }

    $errores = validar_solicitud($datos, $modo);
    if ($id_usuario !== null && $rol_creador === 'Administrador' && obtener_usuario($id_usuario) === null) {
        $errores['id_usuario'] = 'La cuenta seleccionada no existe.';
    }
    if ($errores !== []) {
        return $fallo($errores);
    }

    $valores = [
        ':id_usuario' => $id_usuario,
        ':nombre_contacto' => trim((string) $datos['nombre_contacto']),
        ':telefono_contacto' => limpiar_telefono((string) $datos['telefono_contacto']),
        ':sector' => (string) $datos['sector'],
        ':escala' => (string) $datos['escala'],
        ':id_servicio' => (int) $datos['id_servicio'],
        ':descripcion' => trim((string) $datos['descripcion']),
        ':ubicacion' => trim((string) $datos['ubicacion']),
        ':canal_origen' => $canal,
        ':prioridad' => calcular_prioridad((string) $datos['sector'], (string) $datos['escala']),
        ':notas_internas' => $notas,
    ];

    $pdo = db();
    // Dos altas simultáneas pueden calcular el mismo folio; el índice único lo detecta y se reintenta.
    for ($intento = 1; $intento <= 3; $intento++) {
        $pdo->beginTransaction();
        try {
            $folio = generar_folio($pdo);
            $stmt = $pdo->prepare(
                "INSERT INTO solicitud (folio, id_usuario, nombre_contacto, telefono_contacto, sector, escala, id_servicio,
                    descripcion, ubicacion, canal_origen, prioridad, estado, notas_internas)
                 VALUES (:folio, :id_usuario, :nombre_contacto, :telefono_contacto, :sector, :escala, :id_servicio,
                    :descripcion, :ubicacion, :canal_origen, :prioridad, 'Enviada', :notas_internas)"
            );
            $stmt->execute($valores + [':folio' => $folio]);
            $id = (int) $pdo->lastInsertId();
            $pdo->commit();
            return ['ok' => true, 'errores' => [], 'folio' => $folio, 'id_solicitud' => $id];
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $duplicado = $ex->getCode() === '23000' && str_contains($ex->getMessage(), 'uk_solicitud_folio');
            if (!$duplicado || $intento === 3) {
                throw $ex;
            }
        }
    }
    throw new RuntimeException('No fue posible generar un folio único.');
}

function actualizar_solicitud(int $id_solicitud, array $datos): array
{
    $actual = obtener_solicitud_por_id($id_solicitud);
    if ($actual === null) {
        return ['ok' => false, 'errores' => ['solicitud' => 'La solicitud no existe.']];
    }

    $datos['estado'] = (string) ($datos['estado'] ?? $actual['estado']);
    $errores = validar_solicitud($datos, 'editar_admin', $actual['estado']);
    if ($errores !== []) {
        return ['ok' => false, 'errores' => $errores];
    }

    // Si el formulario no envía fecha_visita se conserva la guardada; si la envía vacía se borra.
    $visita = array_key_exists('fecha_visita', $datos)
        ? normalizar_fecha_entrada((string) $datos['fecha_visita'], true)
        : $actual['fecha_visita'];

    $stmt = db()->prepare(
        'UPDATE solicitud SET nombre_contacto = :nombre_contacto, telefono_contacto = :telefono_contacto,
            sector = :sector, escala = :escala, id_servicio = :id_servicio, descripcion = :descripcion,
            ubicacion = :ubicacion, prioridad = :prioridad, estado = :estado, fecha_visita = :fecha_visita,
            notas_internas = :notas_internas
         WHERE id_solicitud = :id'
    );
    $stmt->execute([
        ':nombre_contacto' => trim((string) $datos['nombre_contacto']),
        ':telefono_contacto' => limpiar_telefono((string) $datos['telefono_contacto']),
        ':sector' => (string) $datos['sector'],
        ':escala' => (string) $datos['escala'],
        ':id_servicio' => (int) $datos['id_servicio'],
        ':descripcion' => trim((string) $datos['descripcion']),
        ':ubicacion' => trim((string) $datos['ubicacion']),
        ':prioridad' => calcular_prioridad((string) $datos['sector'], (string) $datos['escala']),
        ':estado' => $datos['estado'],
        ':fecha_visita' => $visita,
        ':notas_internas' => texto_o_null($datos['notas_internas'] ?? ''),
        ':id' => $id_solicitud,
    ]);
    return ['ok' => true, 'errores' => []];
}

function eliminar_solicitud(int $id_solicitud): bool
{
    $stmt = db()->prepare('DELETE FROM solicitud WHERE id_solicitud = :id');
    $stmt->execute([':id' => $id_solicitud]);
    return $stmt->rowCount() > 0;
}

function escapar_like(string $texto): string
{
    return addcslashes($texto, '\\%_');
}

function listar_solicitudes_admin(array $filtros = [], int $pagina = 1, int $por_pagina = 10): array
{
    $donde = [];
    $params = [];

    $q = trim((string) ($filtros['q'] ?? ''));
    if ($q !== '') {
        $like = '%' . escapar_like($q) . '%';
        $digitos = limpiar_telefono($q);
        $like_tel = $digitos !== '' ? '%' . $digitos . '%' : $like;
        $donde[] = '(s.folio LIKE :q_folio OR s.nombre_contacto LIKE :q_contacto OR u.nombre LIKE :q_usuario
                     OR u.empresa LIKE :q_empresa OR s.telefono_contacto LIKE :q_telefono)';
        $params += [
            ':q_folio' => $like, ':q_contacto' => $like, ':q_usuario' => $like,
            ':q_empresa' => $like, ':q_telefono' => $like_tel,
        ];
    }

    $canal = $filtros['canal'] ?? $filtros['canal_origen'] ?? '';
    foreach ([['estado', 's.estado', ESTADOS, $filtros['estado'] ?? ''],
              ['sector', 's.sector', SECTORES, $filtros['sector'] ?? ''],
              ['canal', 's.canal_origen', CANALES, $canal]] as [$clave, $columna, $validos, $valor]) {
        if (in_array((string) $valor, $validos, true)) {
            $donde[] = "{$columna} = :f_{$clave}";
            $params[":f_{$clave}"] = (string) $valor;
        }
    }

    $desde = normalizar_fecha_entrada((string) ($filtros['desde'] ?? ''));
    if ($desde !== null) {
        $donde[] = 's.fecha_registro >= :desde';
        $params[':desde'] = $desde . ' 00:00:00';
    }
    $hasta = normalizar_fecha_entrada((string) ($filtros['hasta'] ?? ''));
    if ($hasta !== null) {
        $donde[] = 's.fecha_registro <= :hasta';
        $params[':hasta'] = $hasta . ' 23:59:59';
    }
    $clausula = $donde === [] ? '' : ' WHERE ' . implode(' AND ', $donde);

    $stmt = db()->prepare('SELECT COUNT(*) FROM solicitud s LEFT JOIN usuario u ON u.id_usuario = s.id_usuario' . $clausula);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $por_pagina = max(1, $por_pagina);
    $total_paginas = max(1, (int) ceil($total / $por_pagina));
    $pagina = min(max(1, $pagina), $total_paginas);
    $offset = ($pagina - 1) * $por_pagina;

    $ordenes_permitidos = [
        'prioridad' => 's.prioridad DESC, s.fecha_registro ASC, s.id_solicitud ASC',
        'recientes' => 's.fecha_registro DESC, s.id_solicitud DESC',
        'antiguas' => 's.fecha_registro ASC, s.id_solicitud ASC',
    ];
    $clave_orden = (string) ($filtros['orden'] ?? 'prioridad');
    $orden_sql = $ordenes_permitidos[$clave_orden] ?? $ordenes_permitidos['prioridad'];

    $stmt = db()->prepare(
        sql_base_solicitud() . $clausula
        . " ORDER BY {$orden_sql} LIMIT :limite OFFSET :offset"
    );
    foreach ($params as $clave => $valor) {
        $stmt->bindValue($clave, $valor);
    }
    $stmt->bindValue(':limite', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = array_map('normalizar_fila_solicitud', $stmt->fetchAll());

    return [
        'items' => $items,
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina,
        'total_paginas' => $total_paginas,
        'desde_registro' => $total === 0 ? 0 : $offset + 1,
        'hasta_registro' => $total === 0 ? 0 : $offset + count($items),
    ];
}

function listar_solicitudes_cliente(int $id_usuario): array
{
    $stmt = db()->prepare(
        sql_base_solicitud(false)
        . " WHERE s.id_usuario = :id
            ORDER BY (s.estado IN ('Finalizada', 'Rechazada', 'Cancelada')) ASC, s.fecha_registro DESC, s.id_solicitud DESC"
    );
    $stmt->execute([':id' => $id_usuario]);
    return array_map('normalizar_fila_solicitud', $stmt->fetchAll());
}

function obtener_solicitud_cliente(int $id_usuario, string $folio): ?array
{
    $stmt = db()->prepare(sql_base_solicitud(false) . ' WHERE s.id_usuario = :id AND s.folio = :folio LIMIT 1');
    $stmt->execute([':id' => $id_usuario, ':folio' => trim($folio)]);
    $fila = $stmt->fetch();
    return $fila ? normalizar_fila_solicitud($fila) : null;
}
