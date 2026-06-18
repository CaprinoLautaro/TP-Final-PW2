<?php
class RankingModel
{
    private $database;
    public function __construct($database)
    {
        $this->database = $database;
    }

    public function verRanking()
    {
        $resultado = $this->database->query(
            "SELECT
                u.id,
                DENSE_RANK() OVER (ORDER BY u.puntaje_total DESC) AS posicion,
                u.nombre_usuario,
                u.puntaje_total,
                u.nivel,
                COUNT(p.id) AS partidas_jugadas
             FROM usuarios u
             LEFT JOIN partidas p ON p.usuario_id = u.id AND p.estado = 'terminada'
             GROUP BY u.id, u.nombre_usuario, u.puntaje_total, u.nivel
             ORDER BY posicion ASC"
        );

        if (empty($resultado)) return [];

        foreach ($resultado as &$fila) {
            $fila['inicial'] = strtoupper($fila['nombre_usuario'][0]);
            $fila['top3']    = (int)$fila['posicion'] <= 3;
        }

        return $resultado;
    }
}