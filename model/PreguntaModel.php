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
        $this->database->execute(
            "INSERT INTO preguntas (
            enunciado,
            categoria_id,
            creado_por,
            estado
        ) VALUES (?, ?, ?, 'pendiente')",
            [
                $enunciado,
                $categoriaId,
                $usuarioId
            ]
        );

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

    public function obtenerTodasLasPreguntas()
    {
        return $this->database->query(
            "SELECT
            p.id,
            p.enunciado,
            c.nombre AS categoria
         FROM preguntas p
         INNER JOIN categorias c
             ON p.categoria_id = c.id
         ORDER BY p.creado_en DESC"
        );
    }

    public function obtenerPreguntaPorId($preguntaId)
    {
        return $this->database->query(
            "SELECT
            p.id,
            p.enunciado,
            categoria_id 
            FROM preguntas p
            WHERE p.id = ?",
            [$preguntaId]
        );
        return $resultado[0] ?? null;
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

    public function obtenerPreguntasReportadas()
    {
        try {
            $filas = $this->database->query(
                "SELECT
                    p.id,
                    p.enunciado,
                    c.nombre        AS categoria,
                    COUNT(r.id)     AS total_reportes,
                    (
                        SELECT r2.motivo
                        FROM reportes r2
                        WHERE r2.pregunta_id = p.id
                        ORDER BY r2.creado_en DESC
                        LIMIT 1
                    )               AS ultimo_motivo
                FROM preguntas p
                JOIN categorias c  ON c.id = p.categoria_id
                JOIN reportes r    ON r.pregunta_id = p.id
                WHERE p.estado = 'aprobada'
                GROUP BY p.id, p.enunciado, c.nombre
                ORDER BY total_reportes DESC",
                []
            );

            $resultado = [];
            foreach ($filas as $fila) {
                $fila['reportes_plural'] = (int) $fila['total_reportes'] > 1;
                $resultado[] = $fila;
            }

            return $resultado;

        } catch (\Exception $e) {
            return [];
        }
    }

    public function desestimar($preguntaId)
    {
        $this->database->execute(
            "DELETE FROM reportes WHERE pregunta_id = ?",
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

    public function actualizarPregunta($preguntaId, $enunciado, $categoriaId)
    {
        $this->database->execute(
            "UPDATE preguntas 
         SET enunciado = ?, 
             categoria_id = ? 
         WHERE id = ?",
            [$enunciado, $categoriaId, $preguntaId]
        );
    }

    public function actualizarOpcion($preguntaId, $texto, $esCorrecta, $orden)
    {
        // Buscamos la opción por el ID de la pregunta y su número de orden (1, 2, 3 o 4)
        // De esta forma editamos el texto y si es correcta o no en una sola consulta
        $this->database->execute(
            "UPDATE opciones 
         SET texto = ?, 
             es_correcta = ? 
         WHERE pregunta_id = ? AND orden = ?",
            [$texto, $esCorrecta, $preguntaId, $orden]
        );
    }
}