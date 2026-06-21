<?php

class PerfilController
{
    private $renderer;
    private $request;
    private $perfilModel;
    private $partidaModel;

    public function __construct($renderer, $request, $perfilModel, $partidaModel) {
        $this->renderer     = $renderer;
        $this->request      = $request;
        $this->perfilModel  = $perfilModel;
        $this->partidaModel = $partidaModel;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['usuario'])) {
            header("Location: ?controller=login&method=index");
            exit();
        }

        $id      = $_SESSION['usuario']['id'];
        $usuario = $this->perfilModel->buscarPorId($id);

        if (!$usuario) {
            die("Usuario no encontrado.");
        }

        $qr = GenerarQR::generador($id);

        $this->renderer->render('perfilView', [
            'nombre_completo' => $usuario['nombre_completo'],
            'nombre_usuario'  => $usuario['nombre_usuario'],
            'email'           => $usuario['email'],
            'ciudad'          => $usuario['ciudad'],
            'sexo'            => $usuario['sexo'],
            'foto_perfil'     => $usuario['foto_perfil'],
            'inicial'         => strtoupper($usuario['nombre_usuario'][0]),
            'anio_nacimiento' => $usuario['anio_nacimiento'],
            'latitud'         => $usuario['latitud'],
            'longitud'        => $usuario['longitud'],
            'modo_lectura'    => true,
            'qr'              => $qr
        ]);
    }

    public function editarPerfil(){

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if(empty($_SESSION['usuario'])){
            header("Location: ?controller=login&method=index");
            exit();
        }
        $id      = $_SESSION['usuario']['id'];
        $usuario = $this->perfilModel->buscarPorId($id);

        if (!$usuario) {
            die("Usuario no encontrado.");
        }

        $this->renderer->render('perfilView', [
            'nombre_completo' => $usuario['nombre_completo'],
            'nombre_usuario'  => $usuario['nombre_usuario'],
            'email'           => $usuario['email'],
            'ciudad'          => $usuario['ciudad'],
            'sexo'            => $usuario['sexo'],
            'foto_perfil'     => $usuario['foto_perfil'],
            'inicial'         => strtoupper($usuario['nombre_usuario'][0]),
            'anio_nacimiento' => $usuario['anio_nacimiento'],
            'latitud'         => $usuario['latitud'],
            'longitud'        => $usuario['longitud'],
            'modo_edicion'    => true
        ]);
    }

    public function procesarEdicion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['usuario'])) {
            header("Location: ?controller=login&method=index");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id       = $_SESSION['usuario']['id'];
            $nombre   = $_POST['nombre_completo'];
            $ciudad   = $_POST['ciudad'];
            $sexo     = $_POST['sexo'];
            $anio     = $_POST['anio_nacimiento'];
            $latitud  = !empty($_POST['latitud']) ? $_POST['latitud'] : null;
            $longitud = !empty($_POST['longitud']) ? $_POST['longitud'] : null;

            $this->perfilModel->actualizarUsuario($id, $nombre, $ciudad, $sexo, $anio, $latitud, $longitud);

            header("Location: ?controller=perfil&method=index");
            exit();
        } else {
            header("Location: ?controller=perfil&method=index");
            exit();
        }
    }

    public function verPerfil()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['usuario'])) {
            header("Location: ?controller=login&method=index");
            exit();
        }

        $idAjeno = (int) ($_GET['id'] ?? 0);

        if (!$idAjeno) {
            header("Location: ?controller=ranking&method=index");
            exit();
        }

        if ($idAjeno === (int) $_SESSION['usuario']['id']) {
            header("Location: ?controller=perfil&method=index");
            exit();
        }

        $usuario = $this->perfilModel->buscarPerfilPublico($idAjeno);

        if (!$usuario) {
            die("Usuario no encontrado.");
        }

        $partidas = $this->partidaModel->obtenerUltimasPartidas($idAjeno, 3);

         $qr = GenerarQR::generador($idAjeno);

        $this->renderer->render('perfilAjenoView', [
            'nombre_completo'       => $usuario['nombre_completo'],
            'nombre_usuario_jugador'=> $usuario['nombre_usuario'],
            'ciudad'                => $usuario['ciudad'],
            'foto_perfil_jugador'   => $usuario['foto_perfil'],
            'inicial_jugador'       => strtoupper($usuario['nombre_usuario'][0]),
            'latitud'               => $usuario['latitud'],
            'longitud'              => $usuario['longitud'],
            'puntaje_total_jugador' => $usuario['puntaje_total'],
            'nivel_jugador'         => $usuario['nivel'],
            'partidas'              => $partidas,
            'id_jugador'            => $idAjeno,
            'qr'                    => $qr
        ]);
    }
    public function ver()
    {
        $this->verPerfil();
    }
}