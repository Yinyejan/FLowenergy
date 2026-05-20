<?php
/**
 * UsuarioModel.php
 * Modelo de autenticación de usuarios.
 * Usa la columna Id_usuario (actualizada en el SQL v2.0).
 */

require_once(__DIR__ . '/../config/Db.php');

class UsuarioModel {

    /**
     * Valida credenciales. Devuelve el row del usuario o null.
     */
    public static function validarUsuario(int $id_usuario, string $password): ?array {
        try {
            $db   = Db::conectar();
            $stmt = $db->prepare(
                "SELECT * FROM inicio_sesion WHERE Id_usuario = :u AND Contraseña = :p"
            );
            $stmt->execute(['u' => $id_usuario, 'p' => $password]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ?: null;
        } catch (PDOException $e) {
            error_log('[UsuarioModel::validarUsuario] ' . $e->getMessage());
            return null;
        }
    }
}
