<?php

class HomeController
{

    private $renderer;
    private $request;
    private $partidaModel;
    private $rankingModel;

    public function __construct($renderer, $request, $partidaModel, $rankingModel)
    {
        $this->renderer     = $renderer;
        $this->request      = $request;
        $this->partidaModel = $partidaModel;
        $this->rankingModel = $rankingModel;
    }

    public function index()
    {
        session_start();

        $mensajeExito = $_SESSION['mensaje_exito'] ?? null;
        unset($_SESSION['mensaje_exito']);

        $usuarioId = $_SESSION['usuario']['id'] ?? null;

        $usuarioRol = $_SESSION['usuario']['rol_id'] ?? null;

        $partidas = $usuarioId
            ? $this->partidaModel->obtenerUltimasPartidas($usuarioId, 3)
            : [];

        $posicion = null;
        if ($usuarioId) {
            $ranking = $this->rankingModel->verRanking();
            foreach ($ranking as $fila) {
                if ((int)$fila['id'] === (int)$usuarioId) {
                    $posicion = (int)$fila['posicion'];
                    break;
                }
            }
        }

        $this->renderer->render(
            'homeView',
            [
                'mensaje_exito'    => $mensajeExito,
                'partidas'         => $partidas,
                'posicion_ranking' => $posicion,
                'medalla_oro'      => $posicion === 1,
                'medalla_plata'    => $posicion === 2,
                'medalla_bronce'   => $posicion === 3,

                'esAdmin'          => ((int)$usuarioRol === 3)
            ]
        );
    }

}