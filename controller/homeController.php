<?php

class HomeController
{

    private $renderer;
    private $request;

    public function __construct($renderer, $request)
    {
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function index()
    {
        session_start();

        $mensajeExito =
            $_SESSION['mensaje_exito'] ?? null;

        unset($_SESSION['mensaje_exito']);

        $this->renderer->render(
            'homeView',
            [
                'mensaje_exito' => $mensajeExito
            ]
        );
    }


}