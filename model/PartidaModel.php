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
                return 'facil';  
        }
    }

    public function seleccionarPreguntas($usuarioId, $dificultad, $cantidad = 10)
    {
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

    public function seleccionarPreguntaDeCategoria($usuarioId, $categoriaId, $dificultad)
    {
        $preguntas = $this->database->query(
            "SELECT
                p.id, p.enunciado, p.dificultad, p.veces_vista, p.veces_correcta,
                c.id     AS categoria_id,
                c.nombre AS categoria_nombre,
                c.color  AS categoria_color
             FROM preguntas p
             JOIN categorias c ON p.categoria_id = c.id
             WHERE p.estado     = 'aprobada'
               AND p.categoria_id = ?
               AND p.dificultad   = ?
               AND p.id NOT IN (
                   SELECT pv.pregunta_id
                   FROM preguntas_vistas pv
                   WHERE pv.usuario_id = ?
               )
             ORDER BY RAND()
             LIMIT 1",
            [$categoriaId, $dificultad, $usuarioId]
        );

        if (empty($preguntas)) {
            $preguntas = $this->database->query(
                "SELECT
                    p.id, p.enunciado, p.dificultad, p.veces_vista, p.veces_correcta,
                    c.id     AS categoria_id,
                    c.nombre AS categoria_nombre,
                    c.color  AS categoria_color
                 FROM preguntas p
                 JOIN categorias c ON p.categoria_id = c.id
                 WHERE p.estado     = 'aprobada'
                   AND p.categoria_id = ?
                   AND p.id NOT IN (
                       SELECT pv.pregunta_id
                       FROM preguntas_vistas pv
                       WHERE pv.usuario_id = ?
                   )
                 ORDER BY RAND()
                 LIMIT 1",
                [$categoriaId, $usuarioId]
            );
        }

        if (empty($preguntas)) {
            $preguntas = $this->database->query(
                "SELECT
                    p.id, p.enunciado, p.dificultad, p.veces_vista, p.veces_correcta,
                    c.id     AS categoria_id,
                    c.nombre AS categoria_nombre,
                    c.color  AS categoria_color
                 FROM preguntas p
                 JOIN categorias c ON p.categoria_id = c.id
                 WHERE p.estado     = 'aprobada'
                   AND p.categoria_id = ?
                 ORDER BY RAND()
                 LIMIT 1",
                [$categoriaId]
            );
        }

        return $preguntas[0] ?? null;
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

    public function calcularPenalizacion($preguntasPasadas)
    {
        if ($preguntasPasadas === null) return 0;
        if ($preguntasPasadas <= 0) return 3;
        if ($preguntasPasadas === 1) return 2;
        return 1;
    }

    public function terminarPartida($partidaId, $usuarioId, $preguntasPasadas = null, $estado = 'terminada')
    {
        $this->database->execute(
            "UPDATE partidas SET estado = ?, terminada_en = NOW() WHERE id = ?",
            [$estado, $partidaId]
        );

        $resultado = $this->database->query("SELECT puntaje FROM partidas WHERE id = ?", [$partidaId]);
        $puntaje = (int)($resultado[0]['puntaje'] ?? 0);

        if ($puntaje > 0) {
            $this->database->execute(
                "UPDATE usuarios SET puntaje_total = puntaje_total + ? WHERE id = ?",
                [$puntaje, $usuarioId]
            );
        }

        $penalizacion = $this->calcularPenalizacion($preguntasPasadas);
        if ($penalizacion > 0) {
            $this->database->execute(
                "UPDATE usuarios SET puntaje_total = GREATEST(0, puntaje_total - ?) WHERE id = ?",
                [$penalizacion, $usuarioId]
            );
        }

        $this->actualizarNivelUsuario($usuarioId);
        return $puntaje;
    }

    public function obtenerPartidaEnCurso($usuarioId)
    {
        $resultado = $this->database->query(
            "SELECT id FROM partidas 
         WHERE usuario_id = ? AND estado = 'en_curso'
         ORDER BY creado_en DESC LIMIT 1",
            [$usuarioId]
        );
        return $resultado[0] ?? null;
    }

    public function obtenerPuntaje($partidaId)
    {
        $resultado = $this->database->query("SELECT puntaje FROM partidas WHERE id = ?", [$partidaId]);
        return (int)($resultado[0]['puntaje'] ?? 0);
    }

    public function obtenerUltimasPartidas($usuarioId, $cantidad = 3, $totalPreguntas = 10)
    {
        $filas = $this->database->query(
            "SELECT puntaje, terminada_en
             FROM partidas
             WHERE usuario_id = ?
               AND estado = 'terminada'
             ORDER BY terminada_en DESC
             LIMIT ?",
            [$usuarioId, $cantidad]
        );

        $partidas = [];
        foreach ($filas as $fila) {
            $puntaje = (int) $fila['puntaje'];
            $gano    = $puntaje >= $totalPreguntas;

            $partidas[] = [
                'fecha'     => date('d/m/Y', strtotime($fila['terminada_en'])),
                'detalle'   => "{$puntaje}/{$totalPreguntas} preguntas respondidas",
                'resultado' => $gano ? 'ganada' : 'perdida',
                'puntaje'   => $puntaje,
            ];
        }

        return $partidas;
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