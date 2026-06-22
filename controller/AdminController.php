<?php

class AdminController {
    private $model;
    private $renderer;
    public function __construct($adminModel, $renderer) {
        $this->model = $adminModel;
        $this->renderer = $renderer;
    }

    public function index() {
        $filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'siempre';

        $datosPanel = [
            "total_jugadores"   => $this->model->getTotalJugadores(),
            "total_nuevos"      => $this->model->getUsuariosNuevos($filtro),
            "total_partidas"    => $this->model->getTotalPartidas($filtro),
            "total_preguntas"   => $this->model->getTotalPreguntas(),
            "preguntas_juego"   => $this->model->getPreguntasEnJuego(),
            "preguntas_creadas" => $this->model->getPreguntasCreadas($filtro),

            "usuarios_pais"        => $this->model->getUsuariosPorPais($filtro),
            "usuarios_sexo"        => $this->model->getUsuariosPorSexo($filtro),
            "usuarios_edad"        => $this->model->getUsuariosPorEdad($filtro),
            "rendimiento_usuarios" => $this->model->getRendimientoUsuarios($filtro)
        ];
        $this->renderer->render("panelAdminView", $datosPanel);
    }
}