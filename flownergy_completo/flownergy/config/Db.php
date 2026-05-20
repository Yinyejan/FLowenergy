<?php
/**
 * config/Db.php
 * 
 * Conexión a la base de datos usando PDO con patrón Singleton.
 * Solo se crea UNA conexión por ejecución, reutilizada por todos los modelos.
 * 
 * Uso:
 *   $db = Db::conectar();
 *   $stmt = $db->prepare("SELECT ...");
 */

class Db {

    /** @var PDO|null Instancia única de la conexión */
    private static ?PDO $conexion = null;

    /**
     * Retorna la conexión PDO. La crea solo la primera vez.
     * @throws PDOException si no puede conectar
     */
    public static function conectar(): PDO {
        if (self::$conexion === null) {
            $host    = 'localhost';
            $db      = 'flownergyy';
            $usuario = 'root';
            $clave   = '';          // Cambia si tienes contraseña en MySQL
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lanza excepciones en error
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Resultados como array asociativo
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statements reales
            ];

            try {
                self::$conexion = new PDO($dsn, $usuario, $clave, $opciones);
            } catch (PDOException $e) {
                // En producción, loguea el error real y muestra mensaje genérico
                error_log('[Db::conectar] Error de conexión: ' . $e->getMessage());
                die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
            }
        }

        return self::$conexion;
    }

    /** Evita la clonación del singleton */
    private function __clone() {}
}
