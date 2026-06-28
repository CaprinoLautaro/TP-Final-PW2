<?php
class ReporteModel
{
    private $database;

    function __construct($database){
        $this->database = $database;
    }

    public function procesarReporte($usuarioId, $preguntaId, $motivo)
    {
        return $this->database->execute("INSERT INTO reportes_preguntas (pregunta_id, usuario_id, motivo, estado)
                                 VALUES (?, ?, ?, 'pendiente')", [$preguntaId, $usuarioId, $motivo]) > 0;
    }

    public function obtenerPreguntasReportadas()
    {
            $filas = $this->database->query(
                "SELECT
                    p.id,
                    p.enunciado,
                    c.nombre        AS categoria,
                    COUNT(r.id)     AS total_reportes,
                    (
                        SELECT r2.motivo
                        FROM reportes_preguntas r2
                        WHERE r2.pregunta_id = p.id
                        ORDER BY r2.creado_en DESC
                        LIMIT 1
                    )               AS ultimo_motivo
                FROM preguntas p
                JOIN categorias c  ON c.id = p.categoria_id
                JOIN reportes_preguntas r    ON r.pregunta_id = p.id
                WHERE p.estado = 'reportada'
                GROUP BY p.id, p.enunciado, c.nombre
                ORDER BY total_reportes DESC", []
            );

            $resultado = [];
            foreach ($filas as $fila) {
                $fila['reportes_plural'] = (int) $fila['total_reportes'] > 1;
                $resultado[] = $fila;
            }

            return $resultado;
    }

    public function desestimar($preguntaId)
    {
        $this->database->execute(
            "DELETE FROM reportes_preguntas WHERE pregunta_id = ?",
            [$preguntaId]
        );
    }

    public function cambiarEstado($preguntaId, $estado)
    {
        $this->database->execute(
            "UPDATE preguntas SET estado = ? WHERE id = ?",
            [$estado, $preguntaId]
        );
    }
}