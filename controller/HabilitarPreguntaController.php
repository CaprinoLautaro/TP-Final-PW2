<?php

class HabilitarPreguntaController
{
    private $renderer;
    private $preguntaModel;

    public function __construct($renderer, $preguntaModel)
    {
        $this->renderer = $renderer;
        $this->preguntaModel = $preguntaModel;
    }

    public function index()
    {
        session_start();

        $rol = $_SESSION['usuario']['rol_id'] ?? 0;

        if ($rol != 2 && $rol != 3) {
            header('Location: ?controller=home&method=index');
            exit;
        }

        $preguntas = $this->preguntaModel->obtenerPreguntasPendientes();

        $preguntasReportadas = $this->preguntaModel->obtenerPreguntasReportadas(); // BUG 1: era $this->model->

        $this->renderer->render(
            'habilitarPreguntaView',
            [
                'preguntas'            => $preguntas, // BUG 2: le faltaba la coma
                'preguntas_reportadas' => $preguntasReportadas,
            ]
        );
    }

    public function aprobar()
    {
        session_start();

        $preguntaId = $_GET['id'] ?? null;
        $editorId   = $_SESSION['usuario']['id'];

        if ($preguntaId) {
            $this->preguntaModel->aprobarPregunta($preguntaId, $editorId);
        }

        header('Location: ?controller=habilitarPregunta&method=index');
        exit;
    }

    public function rechazar()
    {
        session_start();

        $preguntaId = $_GET['id'] ?? null;

        if ($preguntaId) {
            $this->preguntaModel->rechazarPregunta($preguntaId);
        }

        header('Location: ?controller=habilitarPregunta&method=index');
        exit;
    }

    public function desestimar()
    {
        session_start();

        $rol = $_SESSION['usuario']['rol_id'] ?? 0;

        if ($rol != 2 && $rol != 3) {
            header('Location: ?controller=home&method=index');
            exit;
        }

        $preguntaId = (int) ($_GET['id'] ?? 0);

        if ($preguntaId) {
            $this->preguntaModel->desestimar($preguntaId);
        }

        header('Location: ?controller=habilitarPregunta&method=index');
        exit;
    }
}