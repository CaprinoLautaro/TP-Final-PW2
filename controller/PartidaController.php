<?php

class PartidaController
{
    private $renderer;
    private $request;
    private $partidaModel;
    private $preguntaModel;
    private $userModel;
    private $reporteModel;

    const PREGUNTAS_POR_PARTIDA = 10;
    const LETRAS = ['A', 'B', 'C', 'D'];
    // Tiene que coincidir con la duración de la transición CSS en ruleta.mustache
    const DURACION_RULETA_MS = 3000;

    public function __construct(
        $renderer,
        $request,
        $partidaModel,
        $preguntaModel,
        $userModel,
        $reporteModel
    ) {
        $this->renderer     = $renderer;
        $this->request      = $request;
        $this->partidaModel = $partidaModel;
        $this->preguntaModel = $preguntaModel;
        $this->userModel = $userModel;
        $this->reporteModel = $reporteModel;
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

        $dificultad = $this->partidaModel
            ->dificultadSegunNivel($usuarioId);

        $categorias = $this->preguntaModel->obtenerCategorias();

        if (empty($categorias)) {
            $this->renderer->render(
                "error",
                ["mensaje" => "No hay categorías disponibles en este momento."]
            );
            return;
        }

        $partidaId = $this->partidaModel
            ->crearPartida($usuarioId);

        $_SESSION['partida'] = [
            'id'            => $partidaId,
            'dificultad'    => $dificultad,
            'indice_actual' => 0,
            'puntaje'       => 0,
            'total'         => self::PREGUNTAS_POR_PARTIDA,
        ];

        header("Location: ?controller=partida&method=ruleta");
        exit();

    }

    // ─────────────────────────────────────────────────────────────────
    //  ruleta() → gira la ruleta, elige la categoría y la pregunta de la
    //             ronda actual (todavía no arranca el timer de 15s)
    // ─────────────────────────────────────────────────────────────────
    public function ruleta()
    {
        $this->verificarLogin();

        if (empty($_SESSION['partida'])) {
            header("Location: ?controller=partida&method=nueva");
            exit();
        }

        $sesion = &$_SESSION['partida'];

        if ($sesion['indice_actual'] >= $sesion['total']) {
            header("Location: ?controller=partida&method=resultado");
            exit();
        }

        $usuarioId = $_SESSION['usuario']['id'];

        $categorias = $this->preguntaModel->obtenerCategorias();

        if (empty($categorias)) {
            $this->renderer->render(
                "error",
                ["mensaje" => "No hay categorías disponibles en este momento."]
            );
            return;
        }

        // El server elige la categoría (nunca el cliente). Si la categoría
        // elegida no tiene ninguna pregunta aprobada, probamos con otra.
        $pregunta = null;
        $categoriaElegida = null;
        $candidatas = $categorias;
        $intentos = 0;

        while ($pregunta === null && $intentos < 3 && !empty($candidatas)) {
            $indiceAzar = array_rand($candidatas);
            $categoriaElegida = $candidatas[$indiceAzar];

            $pregunta = $this->partidaModel->seleccionarPreguntaDeCategoria(
                $usuarioId,
                $categoriaElegida['id'],
                $sesion['dificultad']
            );

            if ($pregunta === null) {
                unset($candidatas[$indiceAzar]);
            }
            $intentos++;
        }

        if ($pregunta === null) {
            $this->renderer->render(
                "error",
                ["mensaje" => "No hay preguntas disponibles en este momento."]
            );
            return;
        }

        // Guardamos la pregunta de la ronda. El timer de 15s arranca recién
        // cuando se la muestre en jugar(), no durante el giro de la ruleta.
        $sesion['pregunta_actual_id'] = $pregunta['id'];
        unset($sesion['pregunta_limite']);

        $this->renderer->render(
            "ruleta",
            $this->datosParaRuleta($categorias, $categoriaElegida, $sesion)
        );
    }

    /**
     * Arma el gradiente cónico y el ángulo final de rotación para que la
     * ruleta visualmente termine apuntando a $categoriaElegida.
     */
    private function datosParaRuleta(array $categorias, array $categoriaElegida, array $sesion): array
    {
        $n = count($categorias);
        $anguloPorCategoria = 360 / $n;

        $stops = [];
        $indiceGanador = 0;
        foreach ($categorias as $i => $categoria) {
            $desde = $i * $anguloPorCategoria;
            $hasta = ($i + 1) * $anguloPorCategoria;
            $stops[] = "{$categoria['color']} {$desde}deg {$hasta}deg";

            if ($categoria['id'] === $categoriaElegida['id']) {
                $indiceGanador = $i;
            }
        }
        $gradiente = "conic-gradient(" . implode(', ', $stops) . ")";

        // La flecha apunta hacia arriba (0°). Centramos el giro en la mitad
        // del segmento ganador y le sumamos vueltas completas solo por estética.
        $centroSegmento = ($indiceGanador * $anguloPorCategoria) + ($anguloPorCategoria / 2);
        $vueltasExtra = 5;
        $rotacionFinal = ($vueltasExtra * 360) - $centroSegmento;

        return [
            "gradiente_css"             => $gradiente,
            "rotacion_final"            => $rotacionFinal,
            "categoria_ganadora_nombre" => $categoriaElegida['nombre'],
            "categoria_ganadora_color"  => $categoriaElegida['color'],
            "numero_pregunta"           => $sesion['indice_actual'] + 1,
            "total_preguntas"           => $sesion['total'],
            "duracion_ms"               => self::DURACION_RULETA_MS,
        ];
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

        // Si todavía no se giró la ruleta para esta ronda, hay que girarla primero
        if (empty($sesion['pregunta_actual_id'])) {
            header("Location: ?controller=partida&method=ruleta");
            exit();
        }

        $preguntaId = $sesion['pregunta_actual_id'];
        $ahora = time();

        if (!isset($sesion['pregunta_limite'])) {
            $sesion['pregunta_limite'] = $ahora + 15;
            $tiempoRestante = 15;
        } else {
            $limiteOriginal = $sesion['pregunta_limite'];
            $tiempoRestante = $limiteOriginal - $ahora;

            if ($tiempoRestante <= 0) {
                $this->forzarDerrotaPorTiempo($preguntaId);
                exit();
            }
        }

        $preguntaFila = $this->partidaModel
            ->obtenerPreguntaPorId($preguntaId);

        // La pregunta pudo haber sido rechazada después de seleccionarse:
        // volvemos a girar la ruleta para esta ronda
        if (!$preguntaFila) {
            unset($sesion['pregunta_actual_id'], $sesion['pregunta_limite']);
            header("Location: ?controller=partida&method=ruleta");
            exit();
        }

        $this->partidaModel->registrarVista($usuarioId, $preguntaId);

        $opciones = $this->partidaModel->obtenerOpciones($preguntaId);
        shuffle($opciones); // el orden en la BD es fijo; lo desordenamos para que la correcta no caiga siempre en la misma letra
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
            "tiempo_restante"    => $tiempoRestante,
        ]);
    }

    private function forzarDerrotaPorTiempo($preguntaId){
        $_POST['tiempo_agotado'] = '1';
        $_POST['pregunta_id'] = $preguntaId;
        $this->responder();
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

        $tiempoAgotado = $this->request->post("tiempo_agotado") === "1";

        if($tiempoAgotado) {
            $opcionId = null;
            $preguntaId = (int) $this->request->post("pregunta_id");
        }else{
            $opcionId = (int) $this->request->post("opcion_id");
            $preguntaId = (int) $this->request->post("pregunta_id");

            if(!$opcionId || !$preguntaId) {
                header("Location: ?controller=partida&method=jugar");
                exit();
            }
        }

        $esCorrecta = !$tiempoAgotado && $this->partidaModel->esOpcionCorrecta($opcionId);

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
                    ->terminarPartida($sesion['id'], $usuarioId, null);
                $sesion['puntaje_final'] = $puntajeFinal;
                $sesion['usuario'] =
                    $this->userModel->obtenerUsuarioPorId($usuarioId);

                header("Location: ?controller=partida&method=resultado");
                exit();
            }
            unset($sesion['pregunta_actual_id'], $sesion['pregunta_limite']);
            header("Location: ?controller=partida&method=ruleta");
            exit();

        } else {

            $correcta = $this->partidaModel
                ->obtenerOpcionCorrecta($preguntaId);

            if($tiempoAgotado){
                $sesion['motivo_derrota'] = 'tiempo';
            }else{
                $sesion['motivo_derrota'] = 'error';
            }


            $sesion['respuesta_incorrecta'] = [
                'pregunta_id'    => $preguntaId,
                'correcta_texto' => $correcta['texto'] ?? '—',
            ];

            // $sesion['indice_actual'] ya fue incrementado en aciertos previos,
            // por lo que refleja exactamente cuántas preguntas pasó antes de perder.
            $preguntasPasadas = $sesion['indice_actual'];

            $puntajeFinal = $this->partidaModel
                ->terminarPartida($sesion['id'], $usuarioId, $preguntasPasadas);

            $sesion['puntaje_final'] = $puntajeFinal;
            $sesion['usuario'] =
                $this->userModel->obtenerUsuarioPorId($usuarioId);

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

        $penalizacion = $gano
            ? 0
            : $this->partidaModel->calcularPenalizacion(
                $sesion['indice_actual'] ?? 0
            );

        $motivoDerrota = $sesion['motivo_derrota'] ?? '';
        $perdioPorTiempo = !$gano && $motivoDerrota === 'tiempo';
        $perdioPorError = !$gano && $motivoDerrota !== 'tiempo';

        $datos = [
            "puntaje"              => $sesion['puntaje_final']
                ?? $sesion['puntaje'],
            "total_preguntas"      => $sesion['total'],
            "gano"                 => $gano,
            "perdio"               => !$gano,
            "perdio_por_tiempo"     => $perdioPorTiempo,
            "perdio_por_error"     => $perdioPorError,

            "correcta_texto"       => $sesion['respuesta_incorrecta']['correcta_texto']
                ?? null,
            "penalizacion"         => $penalizacion,
            "hubo_penalizacion"    => $penalizacion > 0,
            "penalizacion_plural"  => $penalizacion > 1,
        ];

        unset($_SESSION['partida']);

        $this->renderer->render("resultado", $datos);
    }

    public function reportar()
    {
        $this->verificarLogin();

        if (empty($_SESSION['partida'])) {
            header("Location: ?controller=home&method=index");
            exit();
        }

        $usuarioId  = $_SESSION['usuario']['id'];
        $pregunta = $this->partidaModel->obtenerPreguntaPorId($_SESSION['partida']['pregunta_actual_id']);

        $this->renderer->render("reportarView", ["pregunta" => $pregunta, "usuario" => $usuarioId]);
    }

    public function procesarReporte() {
        $this->verificarLogin();


        $usuarioId  = $_SESSION['usuario']['id'];;
        $preguntaId = trim($this->request->post("pregunta_id"));
        $motivo     = trim($this->request->post("motivo"));

        $pregunta = $this->partidaModel->obtenerPreguntaPorId($preguntaId);

        if (empty($motivo)) {
            $this->renderer->render('reportarView', [
                'pregunta' => $pregunta,
                'error'    => 'El motivo no puede estar vacío.'
            ]);
        }

        $exito    = $this->reporteModel->procesarReporte($usuarioId, $preguntaId, $motivo);

        if ($exito) {
            $this->preguntaModel->cambiarEstado($preguntaId, "reportada");
            $this->renderer->render("ReporteExitosoView", ["pregunta" => $pregunta]);
        } else {
            $this->render('reportarView', [
                'pregunta' => $pregunta,
                'error'    => 'No pudimos enviar tu reporte. Intentá de nuevo.'
            ]);
        }
    }
}