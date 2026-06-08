<?php

class PerfilController
{
    private $renderer;
    private $request;
    private $perfilModel;

    public function __construct(
        $renderer,
        $request,
        $perfilModel
    ) {
        $this->renderer    = $renderer;
        $this->request     = $request;
        $this->perfilModel = $perfilModel;
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

        $id = $_SESSION['usuario']['id'];

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
            'modo_lectura'    => true
        ]);
    }
    public function editarPerfil(){

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
        if(empty($_SESSION['usuario'])){
            header("Location: ?controller=login&method=index");
            exit();
        }
        $id = $_SESSION['usuario']['id'];
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

            $id = $_SESSION['usuario']['id'];

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
}