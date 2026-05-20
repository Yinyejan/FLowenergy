<?php
/**
 * MedicionModel.php
 * Modelo central de Flownergy.
 * 
 * Responsabilidades:
 *   1. Guardar lecturas del PZEM-004T en BD
 *   2. Detectar anomalías (picos, bajos, voltaje, FP)
 *   3. Generar alertas de mantenimiento preventivo
 *   4. Consultar histórico con filtros de fecha
 *   5. Estadísticas para la gráfica del dashboard
 */

require_once(__DIR__ . '/../config/Db.php');

class MedicionModel {

    // ── Umbrales de detección ─────────────────────────────────────────

    /** Porcentaje sobre el promedio que se considera pico */
    const UMBRAL_PICO_PCT  = 1.30; // +30% del promedio

    /** Porcentaje bajo el promedio que se considera consumo anormal bajo */
    const UMBRAL_BAJO_PCT  = 0.50; // -50% del promedio

    /** Factor de potencia mínimo antes de alertar mantenimiento */
    const UMBRAL_FP_MIN    = 0.75;

    /** Tolerancia de voltaje ±% del voltaje nominal */
    const TOLERANCIA_V_PCT = 0.15; // ±15%

    // ── Guardar lectura ───────────────────────────────────────────────

    /**
     * Inserta una nueva lectura y ejecuta detección de anomalías.
     * @return array ['guardado' => bool, 'alertas' => array]
     */
    public static function guardar(
        string $id_serial,
        float  $voltaje,
        float  $corriente,
        float  $potencia,
        float  $energia,
        float  $frecuencia,
        float  $fp,
        string $estado = 'ok'
    ): array {
        $alertas_generadas = [];

        try {
            $db  = Db::conectar();
            $sql = "INSERT INTO sensores_energia
                        (Id_serial, Voltaje, Corriente, Potencia, Energia, Frecuencia, Factor_potencia, Estado)
                    VALUES
                        (:serial, :v, :c, :p, :e, :f, :fp, :estado)";
            $stmt = $db->prepare($sql);
            $ok   = $stmt->execute([
                'serial' => $id_serial,
                'v'      => $voltaje,
                'c'      => $corriente,
                'p'      => $potencia,
                'e'      => $energia,
                'f'      => $frecuencia,
                'fp'     => $fp,
                'estado' => $estado,
            ]);

            if ($ok) {
                // Obtener voltaje nominal de la máquina para umbrales
                $voltaje_nominal = self::obtenerVoltajeNominal($id_serial);

                // Ejecutar todas las verificaciones de anomalía
                $alertas_generadas = self::detectarAnomalias(
                    $id_serial, $voltaje, $potencia, $fp, $voltaje_nominal
                );
            }

            return ['guardado' => $ok, 'alertas' => $alertas_generadas];

        } catch (PDOException $e) {
            error_log('[MedicionModel::guardar] ' . $e->getMessage());
            return ['guardado' => false, 'alertas' => []];
        }
    }

    // ── Detección de anomalías ────────────────────────────────────────

    /**
     * Evalúa los valores y genera alertas si detecta condiciones anormales.
     * Todas las alertas se persisten en la tabla `alertas`.
     */
    private static function detectarAnomalias(
        string $id_serial,
        float  $voltaje,
        float  $potencia,
        float  $fp,
        float  $voltaje_nominal
    ): array {
        $alertas = [];

        // 1. Verificar voltaje fuera de rango ±15%
        $v_min = $voltaje_nominal * (1 - self::TOLERANCIA_V_PCT);
        $v_max = $voltaje_nominal * (1 + self::TOLERANCIA_V_PCT);

        if ($voltaje > 0 && $voltaje < $v_min) {
            $msg = "Voltaje bajo ({$voltaje}V). Mínimo esperado: {$v_min}V. Riesgo de daño a equipos.";
            self::insertarAlerta($id_serial, 'voltaje_bajo', $msg, $voltaje, $v_min);
            $alertas[] = ['tipo' => 'voltaje_bajo', 'mensaje' => $msg];
        }

        if ($voltaje > $v_max) {
            $msg = "Voltaje alto ({$voltaje}V). Máximo esperado: {$v_max}V. Posible riesgo de incendio.";
            self::insertarAlerta($id_serial, 'voltaje_alto', $msg, $voltaje, $v_max);
            $alertas[] = ['tipo' => 'voltaje_alto', 'mensaje' => $msg];
        }

        // 2. Verificar potencia vs. promedio histórico (últimos 30 días)
        $promedio = self::obtenerPromedioPotencia($id_serial, 30);

        if ($promedio > 0) {
            $umbral_pico = $promedio * self::UMBRAL_PICO_PCT;
            $umbral_bajo = $promedio * self::UMBRAL_BAJO_PCT;

            if ($potencia > $umbral_pico) {
                $msg = "Pico de consumo detectado: {$potencia}W (promedio histórico: {$promedio}W). Verificar carga.";
                self::insertarAlerta($id_serial, 'pico_potencia', $msg, $potencia, $umbral_pico);
                $alertas[] = ['tipo' => 'pico_potencia', 'mensaje' => $msg];
            }

            if ($potencia > 0 && $potencia < $umbral_bajo) {
                $msg = "Consumo anormalmente bajo: {$potencia}W (promedio histórico: {$promedio}W). Posible falla de carga.";
                self::insertarAlerta($id_serial, 'bajo_potencia', $msg, $potencia, $umbral_bajo);
                $alertas[] = ['tipo' => 'bajo_potencia', 'mensaje' => $msg];
            }
        }

        // 3. Verificar factor de potencia (indicador de mantenimiento eléctrico)
        if ($fp > 0 && $fp < self::UMBRAL_FP_MIN) {
            $msg = "Factor de potencia bajo ({$fp}). Posible necesidad de mantenimiento o corrección de reactivos.";
            self::insertarAlerta($id_serial, 'fp_bajo', $msg, $fp, self::UMBRAL_FP_MIN);
            $alertas[] = ['tipo' => 'fp_bajo', 'mensaje' => $msg];
        }

        // 4. Mantenimiento preventivo: FP bajo en 3+ lecturas consecutivas recientes
        if (self::contarAlertasRecientes($id_serial, 'fp_bajo', 3) >= 3) {
            $msg = "MANTENIMIENTO PREVENTIVO recomendado: Factor de potencia bajo de forma persistente.";
            self::insertarAlerta($id_serial, 'mantenimiento', $msg, $fp, self::UMBRAL_FP_MIN);
            $alertas[] = ['tipo' => 'mantenimiento', 'mensaje' => $msg];
        }

        return $alertas;
    }

    // ── Consultas históricas ──────────────────────────────────────────

    /**
     * Devuelve lecturas agrupadas para la gráfica del dashboard.
     * Cada punto representa el promedio de un intervalo de 5 minutos.
     * @param int $horas Número de horas hacia atrás a consultar
     */
    public static function obtenerHistorico(string $id_serial, int $horas = 24): array {
        try {
            $db  = Db::conectar();
            // Agrupar por intervalos de 5 minutos
            $sql = "SELECT
                        DATE_FORMAT(
                            FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(Fecha_hora) / 300) * 300),
                            '%Y-%m-%d %H:%i'
                        ) AS intervalo,
                        ROUND(AVG(Voltaje), 2)         AS voltaje,
                        ROUND(AVG(Corriente), 3)        AS corriente,
                        ROUND(AVG(Potencia), 2)         AS potencia,
                        ROUND(MAX(Potencia), 2)         AS potencia_max,
                        ROUND(AVG(Energia), 4)          AS energia,
                        ROUND(AVG(Frecuencia), 1)       AS frecuencia,
                        ROUND(AVG(Factor_potencia), 3)  AS fp,
                        COUNT(*)                        AS num_lecturas
                    FROM sensores_energia
                    WHERE Id_serial = :serial
                      AND Fecha_hora >= NOW() - INTERVAL :horas HOUR
                    GROUP BY intervalo
                    ORDER BY intervalo ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute(['serial' => $id_serial, 'horas' => $horas]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[MedicionModel::obtenerHistorico] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Devuelve lecturas en un rango de fechas para exportar a CSV.
     */
    public static function obtenerPorRango(string $id_serial, string $desde, string $hasta): array {
        try {
            $db  = Db::conectar();
            $sql = "SELECT
                        Fecha_hora,
                        Voltaje,
                        Corriente,
                        Potencia,
                        Energia,
                        Frecuencia,
                        Factor_potencia,
                        Estado
                    FROM sensores_energia
                    WHERE Id_serial = :serial
                      AND DATE(Fecha_hora) BETWEEN :desde AND :hasta
                    ORDER BY Fecha_hora ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute(['serial' => $id_serial, 'desde' => $desde, 'hasta' => $hasta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[MedicionModel::obtenerPorRango] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Devuelve alertas no vistas para una máquina, más recientes primero.
     */
    public static function obtenerAlertas(string $id_serial, int $limite = 10): array {
        try {
            $db   = Db::conectar();
            $stmt = $db->prepare(
                "SELECT * FROM alertas
                 WHERE Id_serial = :serial
                 ORDER BY Fecha_hora DESC
                 LIMIT :lim"
            );
            $stmt->bindValue('serial', $id_serial);
            $stmt->bindValue('lim', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // ── Helpers internos ──────────────────────────────────────────────

    private static function insertarAlerta(
        string $serial, string $tipo, string $mensaje, float $valor, float $umbral
    ): void {
        try {
            $db   = Db::conectar();
            $stmt = $db->prepare(
                "INSERT INTO alertas (Id_serial, Tipo, Mensaje, Valor, Umbral) VALUES (:s,:t,:m,:v,:u)"
            );
            $stmt->execute(['s' => $serial, 't' => $tipo, 'm' => $mensaje, 'v' => $valor, 'u' => $umbral]);
        } catch (PDOException $e) {
            error_log('[MedicionModel::insertarAlerta] ' . $e->getMessage());
        }
    }

    private static function obtenerPromedioPotencia(string $serial, int $dias): float {
        try {
            $db   = Db::conectar();
            $stmt = $db->prepare(
                "SELECT AVG(Potencia) FROM sensores_energia
                 WHERE Id_serial = :s AND Fecha_hora >= NOW() - INTERVAL :d DAY AND Potencia > 0"
            );
            $stmt->bindValue('s', $serial);
            $stmt->bindValue('d', $dias, PDO::PARAM_INT);
            $stmt->execute();
            return (float)($stmt->fetchColumn() ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    private static function obtenerVoltajeNominal(string $serial): float {
        try {
            $db   = Db::conectar();
            $stmt = $db->prepare("SELECT Voltaje_nominal FROM registro WHERE Id_serial = :s");
            $stmt->execute(['s' => $serial]);
            return (float)($stmt->fetchColumn() ?? 110);
        } catch (PDOException $e) {
            return 110;
        }
    }

    private static function contarAlertasRecientes(string $serial, string $tipo, int $horas): int {
        try {
            $db   = Db::conectar();
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM alertas
                 WHERE Id_serial = :s AND Tipo = :t AND Fecha_hora >= NOW() - INTERVAL :h HOUR"
            );
            $stmt->bindValue('s', $serial);
            $stmt->bindValue('t', $tipo);
            $stmt->bindValue('h', $horas, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
