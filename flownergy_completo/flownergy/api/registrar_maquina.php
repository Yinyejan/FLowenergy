<?php
// api/registrar_maquina.php — Registra una máquina vinculada al usuario en sesión
session_start();
require_once(__DIR__ . '/../models/MaquinaModel.php');

// Solo usuarios autenticados
if (!isset($_SESSION['usuario'])) {
    header('Location: ../views/index.php?ver=acceder');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/index.php?ver=dashboard');
    exit();
}

$serial   = trim($_POST['id_serial']    ?? '');
$tipo     = trim($_POST['nombre_tipo']  ?? '');
$voltaje  = (float)($_POST['voltaje']   ?? 110);
$version  = trim($_POST['version']      ?? '1.0');
$id_user  = (int)$_SESSION['usuario'];

// Validación básica
if (empty($serial) || empty($tipo)) {
    echo "<script>alert('El serial y el tipo son obligatorios.'); window.history.back();</script>";
    exit();
}

// Verificar que el serial no esté ya en uso
if (MaquinaModel::existeSerial($serial)) {
    echo "<script>alert('Ese número serial ya está registrado. Usa uno diferente.'); window.history.back();</script>";
    exit();
}

$ok = MaquinaModel::registrar($serial, $tipo, $voltaje, $version, $id_user);

if ($ok) {
    // Guardar el serial en sesión para usarlo en el dashboard
    $_SESSION['maquina_serial'] = $serial;
    header('Location: ../views/index.php?ver=dashboard&registrado=1');
} else {
    echo "<script>alert('Error al registrar la máquina. Intenta de nuevo.'); window.history.back();</script>";
}
exit();
