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
