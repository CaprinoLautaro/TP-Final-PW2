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
            creado_por
        ) VALUES (?, ?, ?)",
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
}