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
            "SELECT DENSE_RANK() OVER (ORDER BY puntaje_total DESC) AS posicion,
             nombre_usuario, puntaje_total, nivel
             FROM usuarios
             ORDER BY posicion ASC;");
        return $resultado ?? [];
    }
}