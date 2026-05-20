<?php
/**
 * api/guardar_medicion.php
 * 
 * Endpoint AJAX llamado desde el dashboard cada 5 minutos
 * para persistir la lectura actual del PZEM-004T en la BD.
 * También ejecuta detección de anomalías y devuelve alertas.
 * 
 * Método: POST
 * Content-Type: application/json
 * 
 * Body esperado:
 * {
 *   "id_serial":  "001",
 *   "voltaje":    220.5,
 *   "corriente":  0.45,
 *   "potencia":   99.2,
 *   "energia":    0.001,
 *   "frecuencia": 60.0,
 *   "fp":         0.98,
 *   "estado":     "ok"
 * }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Solo usuarios autenticados
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

// Leer cuerpo JSON
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['id_serial'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

require_once(__DIR__ . '/../models/MedicionModel.php');

$resultado = MedicionModel::guardar(
    id_serial:  $body['id_serial'],
    voltaje:    (float)($body['voltaje']    ?? 0),
    corriente:  (float)($body['corriente']  ?? 0),
    potencia:   (float)($body['potencia']   ?? 0),
    energia:    (float)($body['energia']    ?? 0),
    frecuencia: (float)($body['frecuencia'] ?? 0),
    fp:         (float)($body['fp']         ?? 0),
    estado:     $body['estado'] ?? 'ok'
);

echo json_encode($resultado);
exit();
