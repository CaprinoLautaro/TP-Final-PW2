<?php

class PartidaController
{
    private $renderer;
    private $request;
    private $partidaModel;

    const PREGUNTAS_POR_PARTIDA = 10;
    const LETRAS = ['A', 'B', 'C', 'D'];

    public function __construct(
        $renderer,
        $request,
        $partidaModel
    ) {
        $this->renderer     = $renderer;
        $this->request      = $request;
        $this->partidaModel = $partidaModel;
    }

    // ─────────────────────────────────────────────────────────────────
    //  Helpers privados
    // ─────────────────────────────────────────────────────────────────

    private function iniciarSesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function verificarLogin()
    {
        $this->iniciarSesion();

        if (empty($_SESSION['usuario'])) {
            header("Location: ?controller=login&method=index");
            exit();
        }
    }

    // Mustache no tiene índices nativos, así que le agregamos
    // la letra (A/B/C/D) a cada opción antes de pasarla a la vista.
    private function agregarLetras(array $opciones): array
    {
        foreach ($opciones as $i => &$opcion) {
            $opcion['letra'] = self::LETRAS[$i] ?? chr(65 + $i);
        }
        return $opciones;
    }

    // ─────────────────────────────────────────────────────────────────
    //  nueva() → arranca una partida desde cero
    // ─────────────────────────────────────────────────────────────────
    public function nueva()
    {
        $this->verificarLogin();

        $usuarioId = $_SESSION['usuario']['id'];

        $ratio = $this->partidaModel
            ->calcularRatioUsuario($usuarioId);

        $preguntas = $this->partidaModel
            ->seleccionarPreguntas(
                $usuarioId,
                $ratio,
                self::PREGUNTAS_POR_PARTIDA
            );

        if (empty($preguntas)) {
            $this->renderer->render(
                "error",
                ["mensaje" => "No hay preguntas disponibles en este momento."]
            );
            return;
        }

        $partidaId = $this->partidaModel
            ->crearPartida($usuarioId);

        $_SESSION['partida'] = [
            'id'            => $partidaId,
            'pregunta_ids'  => array_column($preguntas, 'id'),
            'indice_actual' => 0,
            'puntaje'       => 0,
            'total'         => count($preguntas),
        ];

        header("Location: ?controller=partida&method=jugar");
        exit();
    }

    // ─────────────────────────────────────────────────────────────────
    //  jugar() → muestra la pregunta actual
    // ─────────────────────────────────────────────────────────────────
    public function jugar()
    {
        $this->verificarLogin();

        if (empty($_SESSION['partida'])) {
            header("Location: ?controller=partida&method=nueva");
            exit();
        }

        $sesion     = &$_SESSION['partida'];
        $indice     = $sesion['indice_actual'];
        $usuarioId  = $_SESSION['usuario']['id'];

        if ($indice >= $sesion['total']) {
            header("Location: ?controller=partida&method=resultado");
            exit();
        }

        $preguntaId   = $sesion['pregunta_ids'][$indice];
        $preguntaFila = $this->partidaModel
            ->obtenerPreguntaPorId($preguntaId);

        // La pregunta pudo haber sido rechazada después de seleccionarse:
        // la salteamos silenciosamente
        if (!$preguntaFila) {
            $sesion['indice_actual']++;
            header("Location: ?controller=partida&method=jugar");
            exit();
        }

        $this->partidaModel->registrarVista($usuarioId, $preguntaId);

        $opciones = $this->partidaModel->obtenerOpciones($preguntaId);
        $opciones = $this->agregarLetras($opciones);  // ← A / B / C / D

        // Porcentaje para la barra de progreso
        $porcentaje = (int) round(
            ($indice / $sesion['total']) * 100
        );

        $this->renderer->render("juego", [
            "pregunta"          => $preguntaFila,
            "opciones"          => $opciones,
            "numero_pregunta"   => $indice + 1,
            "total_preguntas"   => $sesion['total'],
            "puntaje_actual"    => $sesion['puntaje'],
            "porcentaje_progreso" => $porcentaje,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    //  responder() → procesa la opción elegida (POST)
    // ─────────────────────────────────────────────────────────────────
    public function responder()
    {
        $this->verificarLogin();

        if (empty($_SESSION['partida'])) {
            header("Location: ?controller=partida&method=nueva");
            exit();
        }

        $sesion     = &$_SESSION['partida'];
        $usuarioId  = $_SESSION['usuario']['id'];
        $opcionId   = (int) $this->request->post("opcion_id");
        $preguntaId = (int) $this->request->post("pregunta_id");

        if (!$opcionId || !$preguntaId) {
            header("Location: ?controller=partida&method=jugar");
            exit();
        }

        $esCorrecta = $this->partidaModel->esOpcionCorrecta($opcionId);

        $this->partidaModel->registrarRespuesta(
            $sesion['id'],
            $preguntaId,
            $opcionId,
            $esCorrecta
        );

        if ($esCorrecta) {

            $this->partidaModel->sumarPunto($sesion['id']);
            $sesion['puntaje']++;
            $sesion['indice_actual']++;

            if ($sesion['indice_actual'] >= $sesion['total']) {
                $puntajeFinal = $this->partidaModel
                    ->terminarPartida($sesion['id'], $usuarioId);
                $sesion['puntaje_final'] = $puntajeFinal;

                header("Location: ?controller=partida&method=resultado");
                exit();
            }

            header("Location: ?controller=partida&method=jugar");
            exit();

        } else {

            $correcta = $this->partidaModel
                ->obtenerOpcionCorrecta($preguntaId);

            $sesion['respuesta_incorrecta'] = [
                'pregunta_id'    => $preguntaId,
                'correcta_texto' => $correcta['texto'] ?? '—',
            ];

            $puntajeFinal = $this->partidaModel
                ->terminarPartida($sesion['id'], $usuarioId);

            $sesion['puntaje_final'] = $puntajeFinal;

            header("Location: ?controller=partida&method=resultado");
            exit();
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  resultado() → pantalla de fin de partida
    // ─────────────────────────────────────────────────────────────────
    public function resultado()
    {
        $this->verificarLogin();

        if (empty($_SESSION['partida'])) {
            header("Location: ?controller=home&method=index");
            exit();
        }

        $sesion = $_SESSION['partida'];

        $gano = empty($sesion['respuesta_incorrecta']);

        $datos = [
            "puntaje"              => $sesion['puntaje_final']
                                      ?? $sesion['puntaje'],
            "total_preguntas"      => $sesion['total'],
            "gano"                 => $gano,
            "perdio"               => !$gano,   // Mustache no tiene NOT nativo
            "correcta_texto"       => $sesion['respuesta_incorrecta']['correcta_texto']
                                      ?? null,
        ];

        unset($_SESSION['partida']);

        $this->renderer->render("resultado", $datos);
    }
}