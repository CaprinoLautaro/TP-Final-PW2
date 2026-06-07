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
        ]);
    }
}