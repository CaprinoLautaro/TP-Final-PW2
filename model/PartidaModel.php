<?php

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function crearPartida($usuarioId)
    {
        $this->database->execute(
            "INSERT INTO partidas (usuario_id, puntaje, estado)
             VALUES (?, 0, 'en_curso')",
            [$usuarioId]
        );

        return $this->database->lastInsertId();
    }

    public function calcularRatioUsuario($usuarioId)
    {
        $resultado = $this->database->query(
            "SELECT
                COUNT(*)                          AS total,
                SUM(es_correcta)                  AS correctas
             FROM partidas_preguntas pp
             JOIN partidas p ON pp.partida_id = p.id
             WHERE p.usuario_id = ?",
            [$usuarioId]
        );

        $total     = (int) ($resultado[0]['total']     ?? 0);
        $correctas = (int) ($resultado[0]['correctas'] ?? 0);

        if ($total === 0) {
            return 0.5; 
        }

        return round($correctas / $total, 2);
    }

    public function nivelDesdeRatio($ratio)
    {
        if ($ratio < 0.3) return 'facil';
        if ($ratio > 0.7) return 'dificil';
        return 'media';
    }

    public function seleccionarPreguntas($usuarioId, $ratioUsuario, $cantidad = 10)
    {
        $nivel = $this->nivelDesdeRatio($ratioUsuario);

        if ($nivel === 'facil') {
            $condicionDificultad =
                "(p.veces_vista = 0
                  OR (p.veces_correcta / p.veces_vista) > 0.7)";
        } elseif ($nivel === 'dificil') {
            $condicionDificultad =
                "(p.veces_vista > 0
                  AND (p.veces_correcta / p.veces_vista) < 0.3)";
        } else {
            $condicionDificultad =
                "(p.veces_vista = 0
                  OR (
                      (p.veces_correcta / p.veces_vista) >= 0.3
                      AND
                      (p.veces_correcta / p.veces_vista) <= 0.7
                  ))";
        }

        $preguntas = $this->database->query(
            "SELECT
                p.id,
                p.enunciado,
                p.veces_vista,
                p.veces_correcta,
                c.id    AS categoria_id,
                c.nombre AS categoria_nombre,
                c.color  AS categoria_color
             FROM preguntas p
             JOIN categorias c ON p.categoria_id = c.id
             WHERE p.estado = 'aprobada'
               AND $condicionDificultad
               AND p.id NOT IN (
                   SELECT pv.pregunta_id
                   FROM preguntas_vistas pv
                   WHERE pv.usuario_id = ?
               )
             ORDER BY RAND()
             LIMIT ?",
            [$usuarioId, $cantidad]
        );

        if (count($preguntas) < $cantidad) {
            $idsYa = array_column($preguntas, 'id');
            $faltan = $cantidad - count($preguntas);

            $exclude = empty($idsYa)
                ? "0"
                : implode(',', array_map('intval', $idsYa));

            $complemento = $this->database->query(
                "SELECT
                    p.id,
                    p.enunciado,
                    p.veces_vista,
                    p.veces_correcta,
                    c.id    AS categoria_id,
                    c.nombre AS categoria_nombre,
                    c.color  AS categoria_color
                 FROM preguntas p
                 JOIN categorias c ON p.categoria_id = c.id
                 WHERE p.estado = 'aprobada'
                   AND p.id NOT IN ($exclude)
                   AND p.id NOT IN (
                       SELECT pv.pregunta_id
                       FROM preguntas_vistas pv
                       WHERE pv.usuario_id = ?
                   )
                 ORDER BY RAND()
                 LIMIT ?",
                [$usuarioId, $faltan]
            );

            $preguntas = array_merge($preguntas, $complemento);
        }

        if (count($preguntas) < $cantidad) {
            $idsYa  = array_column($preguntas, 'id');
            $faltan = $cantidad - count($preguntas);

            $exclude = empty($idsYa)
                ? "0"
                : implode(',', array_map('intval', $idsYa));

            $ultimoRecurso = $this->database->query(
                "SELECT
                    p.id,
                    p.enunciado,
                    p.veces_vista,
                    p.veces_correcta,
                    c.id    AS categoria_id,
                    c.nombre AS categoria_nombre,
                    c.color  AS categoria_color
                 FROM preguntas p
                 JOIN categorias c ON p.categoria_id = c.id
                 WHERE p.estado = 'aprobada'
                   AND p.id NOT IN ($exclude)
                 ORDER BY RAND()
                 LIMIT ?",
                [$faltan]
            );

            $preguntas = array_merge($preguntas, $ultimoRecurso);
        }

        return $preguntas;
    }

    public function obtenerOpciones($preguntaId)
    {
        return $this->database->query(
            "SELECT id, texto, orden
             FROM opciones
             WHERE pregunta_id = ?
             ORDER BY orden ASC",
            [$preguntaId]
        );
    }

    public function registrarVista($usuarioId, $preguntaId)
    {
        $this->database->execute(
            "INSERT IGNORE INTO preguntas_vistas (usuario_id, pregunta_id)
             VALUES (?, ?)",
            [$usuarioId, $preguntaId]
        );

        $this->database->execute(
            "UPDATE preguntas
             SET veces_vista = veces_vista + 1
             WHERE id = ?",
            [$preguntaId]
        );
    }

    public function registrarRespuesta(
        $partidaId,
        $preguntaId,
        $opcionElegidaId,
        $esCorrecta
    ) {
        $this->database->execute(
            "INSERT INTO partidas_preguntas
                (partida_id, pregunta_id, opcion_elegida_id, es_correcta)
             VALUES (?, ?, ?, ?)",
            [$partidaId, $preguntaId, $opcionElegidaId, $esCorrecta ? 1 : 0]
        );

        if ($esCorrecta) {
            $this->database->execute(
                "UPDATE preguntas
                 SET veces_correcta = veces_correcta + 1
                 WHERE id = ?",
                [$preguntaId]
            );
        }
    }

    public function esOpcionCorrecta($opcionId)
    {
        $resultado = $this->database->query(
            "SELECT es_correcta FROM opciones WHERE id = ?",
            [$opcionId]
        );

        return isset($resultado[0]) && (bool) $resultado[0]['es_correcta'];
    }

    public function obtenerOpcionCorrecta($preguntaId)
    {
        $resultado = $this->database->query(
            "SELECT id, texto
             FROM opciones
             WHERE pregunta_id = ? AND es_correcta = 1
             LIMIT 1",
            [$preguntaId]
        );

        return $resultado[0] ?? null;
    }

    public function sumarPunto($partidaId)
    {
        $this->database->execute(
            "UPDATE partidas
             SET puntaje = puntaje + 1
             WHERE id = ?",
            [$partidaId]
        );
    }

    public function terminarPartida($partidaId, $usuarioId)
    {
        $this->database->execute(
            "UPDATE partidas
             SET estado = 'terminada', terminada_en = NOW()
             WHERE id = ?",
            [$partidaId]
        );

        $resultado = $this->database->query(
            "SELECT puntaje FROM partidas WHERE id = ?",
            [$partidaId]
        );

        $puntaje = (int) ($resultado[0]['puntaje'] ?? 0);

        $this->database->execute(
            "UPDATE usuarios
             SET puntaje_total = puntaje_total + ?
             WHERE id = ?",
            [$puntaje, $usuarioId]
        );

        return $puntaje;
    }

    public function obtenerPuntaje($partidaId)
    {
        $resultado = $this->database->query(
            "SELECT puntaje FROM partidas WHERE id = ?",
            [$partidaId]
        );

        return (int) ($resultado[0]['puntaje'] ?? 0);
    }


    public function obtenerPreguntaPorId($preguntaId)
    {
        $resultado = $this->database->query(
            "SELECT
                p.id,
                p.enunciado,
                p.veces_vista,
                p.veces_correcta,
                c.id     AS categoria_id,
                c.nombre AS categoria_nombre,
                c.color  AS categoria_color
             FROM preguntas p
             JOIN categorias c ON p.categoria_id = c.id
             WHERE p.id = ? AND p.estado = 'aprobada'
             LIMIT 1",
            [$preguntaId]
        );

        return $resultado[0] ?? null;
    }
}