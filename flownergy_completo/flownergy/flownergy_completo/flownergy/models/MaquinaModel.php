<?php
/**
 * MaquinaModel.php
 * Modelo para gestión de máquinas registradas.
 * Maneja registro, consulta y verificación por usuario.
 */

require_once(__DIR__ . '/../config/Db.php');

class MaquinaModel {

    /**
     * Registra una nueva máquina vinculada a un usuario.
     * @return bool true si se insertó correctamente
     */
    public static function registrar(string $serial, string $tipo, float $voltaje, string $version, int $id_usuario): bool {
        try {
            $db  = Db::conectar();
            $sql = "INSERT INTO registro (Id_serial, Nombre_tipo, Voltaje_nominal, Version, Id_usuario)
                    VALUES (:serial, :tipo, :voltaje, :version, :id_usuario)";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'serial'     => $serial,
                'tipo'       => $tipo,
                'voltaje'    => $voltaje,
                'version'    => $version,
                'id_usuario' => $id_usuario,
            ]);
        } catch (PDOException $e) {
            error_log('[MaquinaModel::registrar] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Devuelve la máquina de un usuario, o null si no tiene ninguna.
     */
    public static function obtenerPorUsuario(int $id_usuario): ?array {
        try {
            $db   = Db::conectar();
            $stmt = $db->prepare("SELECT * FROM registro WHERE Id_usuario = :id LIMIT 1");
            $stmt->execute(['id' => $id_usuario]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('[MaquinaModel::obtenerPorUsuario] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si ya existe un serial registrado (evita duplicados).
     */
    public static function existeSerial(string $serial): bool {
        try {
            $db   = Db::conectar();
            $stmt = $db->prepare("SELECT COUNT(*) FROM registro WHERE Id_serial = :serial");
            $stmt->execute(['serial' => $serial]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
