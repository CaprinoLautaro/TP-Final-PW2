<?php

class HomeController
{

    private $renderer;
    private $request;
    private $partidaModel;

    public function __construct($renderer, $request, $partidaModel)
    {
        $this->renderer = $renderer;
        $this->request = $request;
        $this->partidaModel = $partidaModel;
    }

    public function index()
    {
        session_start();

        $mensajeExito =
            $_SESSION['mensaje_exito'] ?? null;

        unset($_SESSION['mensaje_exito']);

        $usuarioId = $_SESSION['usuario']['id'] ?? null;

        $partidas = $usuarioId
            ? $this->partidaModel->obtenerUltimasPartidas($usuarioId, 3)
            : [];

        $this->renderer->render(
            'homeView',
            [
                'mensaje_exito' => $mensajeExito,
                'partidas'      => $partidas,
            ]
        );
    }


}