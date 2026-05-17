<?php
// ================================================================
// api/validar.php  — Autenticación de usuario
// ================================================================
session_start();
require_once(__DIR__ . '/../models/UsuarioModel.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/index.php?ver=acceder');
    exit();
}

$id_usuario = (int)($_POST['usuario'] ?? 0);
$password   = $_POST['password'] ?? '';

$datos = UsuarioModel::validarUsuario($id_usuario, $password);

if ($datos) {
    $_SESSION['usuario']    = $datos['Id_usuario'];
    $_SESSION['id_usuario'] = $datos['Id_usuario'];
    header('Location: ../views/index.php?ver=dashboard');
} else {
    header('Location: ../views/index.php?ver=acceder&error=1');
}
exit();
