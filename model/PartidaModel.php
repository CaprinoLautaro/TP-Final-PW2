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

    /**
     * Devuelve la dificultad de preguntas que le corresponde al usuario
     * según su nivel acumulado: Malo → facil, Bueno → media, Capo → dificil.
     */
    public function dificultadSegunNivel($usuarioId)
    {
        $resultado = $this->database->query(
            "SELECT nivel FROM usuarios WHERE id = ?",
            [$usuarioId]
        );

        $nivel = strtolower($resultado[0]['nivel'] ?? 'malo');

        switch ($nivel) {
            case 'capo':
                return 'dificil';
            case 'bueno':
                return 'media';
            default:
                return 'facil';   // malo o null
        }
    }

    public function seleccionarPreguntas($usuarioId, $dificultad, $cantidad = 10)
    {
        // Primer intento: preguntas del nivel exacto no vistas aún
        $preguntas = $this->database->query(
            "SELECT
                p.id,
                p.enunciado,
                p.dificultad,
                p.veces_vista,
                p.veces_correcta,
                c.id     AS categoria_id,
                c.nombre AS categoria_nombre,
                c.color  AS categoria_color
             FROM preguntas p
             JOIN categorias c ON p.categoria_id = c.id
             WHERE p.estado    = 'aprobada'
               AND p.dificultad = ?
               AND p.id NOT IN (
                   SELECT pv.pregunta_id
                   FROM preguntas_vistas pv
                   WHERE pv.usuario_id = ?
               )
             ORDER BY RAND()
             LIMIT ?",
            [$dificultad, $usuarioId, $cantidad]
        );

        // Segundo intento: completar con cualquier dificultad no vista
        if (count($preguntas) < $cantidad) {
            $idsYa = array_column($preguntas, 'id');
            $faltan = $cantidad - count($preguntas);
            $exclude = empty($idsYa) ? "0" : implode(',', array_map('intval', $idsYa));

            $complemento = $this->database->query(
                "SELECT
                    p.id,
                    p.enunciado,
                    p.dificultad,
                    p.veces_vista,
                    p.veces_correcta,
                    c.id     AS categoria_id,
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

        // Último recurso: preguntas ya vistas (no quedan nuevas)
        if (count($preguntas) < $cantidad) {
            $idsYa = array_column($preguntas, 'id');
            $faltan = $cantidad - count($preguntas);
            $exclude = empty($idsYa) ? "0" : implode(',', array_map('intval', $idsYa));

            $ultimoRecurso = $this->database->query(
                "SELECT
                    p.id,
                    p.enunciado,
                    p.dificultad,
                    p.veces_vista,
                    p.veces_correcta,
                    c.id     AS categoria_id,
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
             VALUES (?, ?)", [$usuarioId, $preguntaId]
        );

        $this->database->execute(
            "UPDATE preguntas
             SET veces_vista = veces_vista + 1
             WHERE id = ?", [$preguntaId]
        );
    }

    public function registrarRespuesta($partidaId, $preguntaId, $opcionElegidaId, $esCorrecta)
    {
        $this->database->execute("INSERT INTO partidas_preguntas (partida_id, pregunta_id, opcion_elegida_id, es_correcta)
                                  VALUES (?, ?, ?, ?)", [$partidaId, $preguntaId, $opcionElegidaId, $esCorrecta ? 1 : 0]);

        if ($esCorrecta) {
            $this->database->execute(
                "UPDATE preguntas
                 SET veces_correcta = veces_correcta + 1
                 WHERE id = ?", [$preguntaId]
            );
        }
        $this->actualizarNivelDificultadDePreguntas($preguntaId);
    }

    public function esOpcionCorrecta($opcionId)
    {
        $resultado = $this->database->query("SELECT es_correcta FROM opciones WHERE id = ?", [$opcionId]);
        return isset($resultado[0]) && (bool)$resultado[0]['es_correcta'];
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

    /**
     * Calcula cuántos puntos se restan al perder según en qué pregunta ocurrió.
     *   Pregunta 1 perdida  (pasadas=0) → -3
     *   Pregunta 2 perdida  (pasadas=1) → -2
     *   Pregunta 3+ perdida             → -1
     *   Victoria            (null)      →  0
     */
    public function calcularPenalizacion($preguntasPasadas)
    {
        if ($preguntasPasadas === null) return 0;
        if ($preguntasPasadas <= 0) return 3;
        if ($preguntasPasadas === 1) return 2;
        return 1;
    }

    public function terminarPartida($partidaId, $usuarioId, $preguntasPasadas = null)
    {
        $this->database->execute(
            "UPDATE partidas
             SET estado = 'terminada', terminada_en = NOW()
             WHERE id = ?", [$partidaId]
        );

        $resultado = $this->database->query("SELECT puntaje FROM partidas WHERE id = ?", [$partidaId]);

        $puntaje = (int)($resultado[0]['puntaje'] ?? 0);

        // Sumar puntos ganados en la partida
        if ($puntaje > 0) {
            $this->database->execute(
                "UPDATE usuarios
                 SET puntaje_total = puntaje_total + ?
                 WHERE id = ?",
                [$puntaje, $usuarioId]
            );
        }

        // Restar penalización si perdió (nunca baja de 0)
        $penalizacion = $this->calcularPenalizacion($preguntasPasadas);
        if ($penalizacion > 0) {
            $this->database->execute(
                "UPDATE usuarios
                 SET puntaje_total = GREATEST(0, puntaje_total - ?)
                 WHERE id = ?",
                [$penalizacion, $usuarioId]
            );
        }
        $this->actualizarNivelUsuario($usuarioId);
        return $puntaje;
    }

    public function obtenerPuntaje($partidaId)
    {
        $resultado = $this->database->query("SELECT puntaje FROM partidas WHERE id = ?", [$partidaId]);
        return (int)($resultado[0]['puntaje'] ?? 0);
    }


    public function obtenerPreguntaPorId($preguntaId)
    {
        $resultado = $this->database->query(
            "SELECT p.id, p.enunciado, p.veces_vista, p.veces_correcta,
                c.id     AS categoria_id,
                c.nombre AS categoria_nombre,
                c.color  AS categoria_color
             FROM preguntas p JOIN categorias c ON p.categoria_id = c.id
             WHERE p.id = ? AND p.estado = 'aprobada'
             LIMIT 1", [$preguntaId]
        );
        return $resultado[0] ?? null;
    }

    public function obtenerNivelUsuario($usuarioId)
    {
        $resultado = $this->database->query("SELECT nivel FROM usuarios WHERE id = ?", [$usuarioId]);
        return $resultado[0]['nivel'] ?? 'Principiante';
    }

    /* public function actualizarNivelUsuario($usuarioId)
     {
         $resultado = $this->database->query(
             "SELECT puntaje_total
          FROM usuarios
          WHERE id = ?",
             [$usuarioId]
         );

         $puntaje = (int) ($resultado[0]['puntaje_total'] ?? 0);

         $nivel = 'malo';

         if ($puntaje >= 15) {
             $nivel = 'capo';
         } elseif ($puntaje >= 10) {
             $nivel = 'bueno';
         }

         $this->database->execute(
             "UPDATE usuarios
          SET nivel = ?
          WHERE id = ?",
             [$nivel, $usuarioId]
         );
     }*/

    public function actualizarNivelUsuario($usuarioId)
    {
        $resultado = $this->database->query(
            "SELECT COUNT(*) AS total,
             SUM(CASE WHEN pp.es_correcta = 1 THEN 1 ELSE 0 END) AS correctas
             FROM partidas_preguntas pp INNER JOIN partidas p ON p.id = pp.partida_id
             WHERE p.usuario_id = ?", [$usuarioId]
        );

        $total = (int)($resultado[0]['total'] ?? 0);
        $correctas = (int)($resultado[0]['correctas'] ?? 0);

        if ($total === 0)
            $nivel = 'Malo';
         else {
            $ratio = $correctas / $total;

            if ($ratio > 0.70) {
                $nivel = 'Capo';
            } elseif ($ratio >= 0.30) {
                $nivel = 'Bueno';
            } else {
                $nivel = 'Malo';
            }
        }

        $this->database->execute(
            "UPDATE usuarios
             SET nivel = ?
             WHERE id = ?", [$nivel, $usuarioId]
        );
    }

    private function actualizarNivelDificultadDePreguntas($preguntaId)
    {
        $resultado = $this->database->query("SELECT veces_vista, veces_correcta FROM preguntas WHERE id = ?;", [$preguntaId]);

        if (!empty($resultado) && $resultado[0]['veces_vista'] > 0) {
            $porcentaje = ($resultado[0]['veces_correcta'] / $resultado[0]['veces_vista']) * 100;

            switch (true) {
                case $porcentaje >= 70:
                    $dificultad = 'facil';
                    break;
                case $porcentaje <= 30:
                    $dificultad = 'dificil';
                    break;
                default:
                    $dificultad = 'media';
                    break;
            }

            $this->database->execute("UPDATE preguntas 
                                      SET dificultad = ? 
                                      WHERE id = ?;", [$dificultad, $preguntaId]);
        }
    }
}