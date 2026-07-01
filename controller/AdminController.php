<?php

class AdminController {
    private $model;
    private $renderer;

    public function __construct($adminModel, $renderer) {
        $this->model    = $adminModel;
        $this->renderer = $renderer;
    }

    public function index() {
        $filtro = $_GET['filtro'] ?? 'siempre';

        $usuariosPais  = $this->model->getUsuariosPorPais($filtro);
        $usuariosSexo  = $this->model->getUsuariosPorSexo($filtro);
        $usuariosEdad  = $this->model->getUsuariosPorEdad($filtro);
        $rendimiento   = $this->model->getRendimientoUsuarios($filtro);

        $this->renderer->render("panelAdminView", [
            "total_jugadores"   => $this->model->getTotalJugadores(),
            "total_nuevos"      => $this->model->getUsuariosNuevos($filtro),
            "total_partidas"    => $this->model->getTotalPartidas($filtro),
            "total_preguntas"   => $this->model->getTotalPreguntas(),
            "preguntas_juego"   => $this->model->getPreguntasEnJuego(),
            "preguntas_creadas" => $this->model->getPreguntasCreadas($filtro),

            "usuarios_pais"        => $usuariosPais,
            "usuarios_sexo"        => $usuariosSexo,
            "usuarios_edad"        => $usuariosEdad,
            "rendimiento_usuarios" => $rendimiento,

            "json_pais"        => json_encode($usuariosPais),
            "json_sexo"        => json_encode($usuariosSexo),
            "json_edad"        => json_encode($usuariosEdad),
            "json_rendimiento" => json_encode($rendimiento),
        ]);
    }
}