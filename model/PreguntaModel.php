<?php
class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerCategorias()
    {
        return $this->database->query(
            "SELECT id, nombre, color
             FROM categorias
             WHERE activa = 1
             ORDER BY id"
        );
    }
    public function crearPregunta(
        $enunciado,
        $categoriaId,
        $usuarioId
    )
    {
        $this->database->execute("INSERT INTO preguntas (enunciado, categoria_id,
                                  creado_por, estado) VALUES (?, ?, ?, 'pendiente')",
                                 [$enunciado, $categoriaId, $usuarioId]);
        return $this->database->lastInsertId();
    }

    public function crearOpcion(
        $preguntaId,
        $texto,
        $esCorrecta,
        $orden
    )

    {
        $this->database->execute(
            "INSERT INTO opciones (
            pregunta_id,
            texto,
            es_correcta,
            orden
        ) VALUES (?, ?, ?, ?)",
            [
                $preguntaId,
                $texto,
                $esCorrecta,
                $orden
            ]
        );
    }

    public function obtenerPreguntasPendientes()
    {
        return $this->database->query(
            "SELECT
            p.id,
            p.enunciado,
            c.nombre AS categoria
         FROM preguntas p
         INNER JOIN categorias c
             ON p.categoria_id = c.id
         WHERE p.estado = 'pendiente'
         ORDER BY p.creado_en DESC"
        );
    }

    public function aprobarPregunta($preguntaId, $editorId)
    {
        $this->database->execute(
            "UPDATE preguntas
         SET estado = 'aprobada',
             aprobada_por = ?
         WHERE id = ?",
            [
                $editorId,
                $preguntaId
            ]
        );
    }

    public function rechazarPregunta($preguntaId)
    {
        $this->database->execute(
            "UPDATE preguntas
         SET estado = 'rechazada'
         WHERE id = ?",
            [$preguntaId]
        );
    }

    public function verificarAutoBaja($preguntaId, $umbral = 10)
    {
        $resultado = $this->database->query(
            "SELECT COUNT(*) AS total
            FROM reportes
            WHERE pregunta_id = ?",
            [$preguntaId]
        );

        $total = (int) ($resultado[0]['total'] ?? 0);

        if ($total >= $umbral) {
            $this->database->execute(
                "UPDATE preguntas
                SET estado = 'rechazada'
                WHERE id = ? AND estado = 'aprobada'",
                [$preguntaId]
            );
        }
    }

    public function cambiarEstado($preguntaId, $estado)
    {
        $this->database->execute(
            "UPDATE preguntas SET estado = ? WHERE id = ?",
            [$estado, $preguntaId]
        );
    }
}