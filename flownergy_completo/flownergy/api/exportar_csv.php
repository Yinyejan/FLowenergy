<?php
/**
 * api/exportar_csv.php
 * 
 * Exporta lecturas del PZEM-004T a CSV.
 * Soporta filtro por rango de fechas.
 * 
 * Parámetros GET:
 *   id_serial  (string) — serial de la máquina
 *   desde      (date)   — fecha inicio YYYY-MM-DD
 *   hasta      (date)   — fecha fin   YYYY-MM-DD
 * 
 * Ejemplo: exportar_csv.php?id_serial=001&desde=2026-05-01&hasta=2026-05-17
 * Nombre del archivo descargado: 001_2026-05-01_2026-05-17.csv
 */

session_start();
require_once(__DIR__ . '/../models/MedicionModel.php');

// Solo usuarios autenticados
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit('No autorizado');
}

$serial = trim($_GET['id_serial'] ?? '');
$desde  = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$hasta  = $_GET['hasta'] ?? date('Y-m-d');

if (empty($serial)) {
    exit('Serial requerido');
}

// Validar formato de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    exit('Formato de fecha inválido. Use YYYY-MM-DD');
}

$datos = MedicionModel::obtenerPorRango($serial, $desde, $hasta);

// ── Generar CSV ───────────────────────────────────────────────────────
$nombre_archivo = "{$serial}_{$desde}_{$hasta}.csv";

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$nombre_archivo}\"");
header('Cache-Control: no-cache, no-store, must-revalidate');

$salida = fopen('php://output', 'w');

// BOM UTF-8 para que Excel abra correctamente con tildes
fprintf($salida, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezado del CSV
fputcsv($salida, [
    'Fecha y Hora',
    'Voltaje (V)',
    'Corriente (A)',
    'Potencia (W)',
    'Energía (kWh)',
    'Frecuencia (Hz)',
    'Factor de Potencia',
    'Estado',
    'Serial Máquina',
], ',');

// Filas de datos
foreach ($datos as $fila) {
    fputcsv($salida, [
        $fila['Fecha_hora'],
        $fila['Voltaje'],
        $fila['Corriente'],
        $fila['Potencia'],
        $fila['Energia'],
        $fila['Frecuencia'],
        $fila['Factor_potencia'],
        $fila['Estado'],
        $serial,
    ], ',');
}

fclose($salida);
exit();
