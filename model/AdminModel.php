<?php

class AdminModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }
    private function getCondicionFecha($filtro, $columnaFecha)
    {
        switch ($filtro) {
            case 'dia':
                return " AND DATE($columnaFecha) = CURDATE()";
            case 'semana':
                return " AND $columnaFecha >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            case 'mes':
                return " AND $columnaFecha >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            case 'anio':
                return " AND $columnaFecha >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            default:
                return "";
        }
    }
    public function getTotalJugadores()
    {
        $resultado = $this->database->query("SELECT COUNT(*) AS total FROM usuarios");
        return $resultado[0]['total'] ?? 0;
    }

    public function getUsuariosNuevos($filtro)
    {
        $condicion = $this->getCondicionFecha($filtro, 'creado_en');
        $resultado = $this->database->query("SELECT COUNT(*) AS total FROM usuarios WHERE 1=1" . $condicion);
        return $resultado[0]['total'] ?? 0;
    }

    public function getTotalPartidas($filtro)
    {
        $condicion = $this->getCondicionFecha($filtro, 'creado_en');
        $resultado = $this->database->query("SELECT COUNT(*) AS total FROM partidas WHERE 1=1" . $condicion);
        return $resultado[0]['total'] ?? 0;
    }

    public function getPreguntasEnJuego()
    {
        $resultado = $this->database->query("SELECT COUNT(*) AS total FROM preguntas WHERE estado = 'aprobada'");
        return $resultado[0]['total'] ?? 0;
    }

    public function getPreguntasCreadas($filtro)
    {
        $condicion = $this->getCondicionFecha($filtro, 'creado_en');
        $resultado = $this->database->query("SELECT COUNT(*) AS total FROM preguntas WHERE 1=1" . $condicion);
        return $resultado[0]['total'] ?? 0;
    }

    public function getTotalPreguntas()
    {
        // Cuenta todas las filas de la tabla preguntas
        $resultado = $this->database->query("SELECT COUNT(*) AS total FROM preguntas");
        return $resultado[0]['total'] ?? 0;
    }
    public function getUsuariosPorPais($filtro)
    {
        $condicion = $this->getCondicionFecha($filtro, 'u.creado_en');
        $totalUsuarios = $this->getUsuariosNuevos($filtro);

        if ($totalUsuarios == 0) return [];

        $sql = "SELECT p.nombre AS pais, COUNT(u.id) AS cantidad 
                FROM usuarios u
                JOIN paises p ON u.pais_id = p.id
                WHERE 1=1" . $condicion . " 
                GROUP BY p.id, p.nombre 
                ORDER BY cantidad DESC";

        $resultados = $this->database->query($sql);

        foreach ($resultados as &$fila) {
            $fila['porcentaje'] = round(($fila['cantidad'] / $totalUsuarios) * 100, 1);
        }

        return $resultados;
    }

    public function getUsuariosPorSexo($filtro)
    {
        $condicion = $this->getCondicionFecha($filtro, 'creado_en');

        $sql = "SELECT CASE 
                        WHEN sexo IS NULL OR sexo = '' THEN 'Prefiero no cargarlo'
                        ELSE sexo
                    END AS sexo,
                    COUNT(*) AS cantidad 
                FROM usuarios 
                WHERE 1=1" . $condicion . "
                GROUP BY CASE 
                            WHEN sexo IS NULL OR sexo = '' THEN 'Prefiero no cargarlo'
                            ELSE sexo
                        END
                ORDER BY cantidad DESC";

        $resultados = $this->database->query($sql);

        if (empty($resultados)) return [];

        $total = array_sum(array_column($resultados, 'cantidad'));

        foreach ($resultados as &$fila) {
            $fila['porcentaje'] = round(($fila['cantidad'] / $total) * 100, 1);
        }

        return $resultados;
    }

    public function getUsuariosPorEdad($filtro)
    {
        $condicion = $this->getCondicionFecha($filtro, 'creado_en');
        $totalUsuarios = $this->getUsuariosNuevos($filtro);

        if ($totalUsuarios == 0) return [];

        $sql = "SELECT 
                    CASE 
                        WHEN (YEAR(CURDATE()) - anio_nacimiento) < 18 THEN 'Menores de 18'
                        WHEN (YEAR(CURDATE()) - anio_nacimiento) BETWEEN 18 AND 65 THEN 'Adultos (18-65)'
                        ELSE 'Mayores de 65'
                    END AS grupo,
                    COUNT(*) AS cantidad
                FROM usuarios
                WHERE 1=1" . $condicion . "
                GROUP BY grupo
                ORDER BY cantidad DESC";

        $resultados = $this->database->query($sql);

        foreach ($resultados as &$fila) {
            $fila['porcentaje'] = round(($fila['cantidad'] / $totalUsuarios) * 100, 1);
        }

        return $resultados;
    }

    public function getRendimientoUsuarios($filtro)
    {
        $condicion = $this->getCondicionFecha($filtro, 'pp.respondida_en');

        $sql = "SELECT 
                    u.nombre_usuario AS username, 
                    COUNT(DISTINCT p.id) AS partidas,
                    ROUND((SUM(pp.es_correcta) / COUNT(pp.id)) * 100, 1) AS porcentaje_aciertos
                FROM usuarios u
                JOIN partidas p ON u.id = p.usuario_id
                JOIN partidas_preguntas pp ON p.id = pp.partida_id
                WHERE 1=1" . $condicion . "
                GROUP BY u.id, u.nombre_usuario
                ORDER BY porcentaje_aciertos DESC
                LIMIT 10";

        return $this->database->query($sql);
    }
}