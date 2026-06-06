<?php

class LoginController
{
    private $renderer;
    private $request;
    private $usuarioModel;

    public function __construct(
        $renderer,
        $request,
        $usuarioModel
    ) {

        $this->renderer =
            $renderer;

        $this->request =
            $request;

        $this->usuarioModel =
            $usuarioModel;
    }

    public function index()
    {
        $success =
            $this->request->get(
                "success"
            );

        $this->renderer->render(
            "login",
            [
                "success" =>
                    $success
            ]
        );
    }

    public function autenticar()
    {
        $nombreUsuario =
            trim(
                $this->request->post(
                    "nombre_usuario"
                )
            );

        $contrasenia =
            $this->request->post(
                "contrasenia"
            );

        $usuario =
            $this->usuarioModel
                ->buscarPorNombreUsuario(
                    $nombreUsuario
                );

        // Usuario no existe
        if (!$usuario) {

            die(
            "Usuario no encontrado"
            );
        }

        // Cuenta no activada
        if (
            !$usuario["activo"]
        ) {

            die(
            "Debés activar tu cuenta antes de iniciar sesión."
            );
        }

        // Contraseña incorrecta
        if (
            !password_verify(
                $contrasenia,
                $usuario[
                "contrasenia"
                ]
            )
        ) {

            die(
            "Contraseña incorrecta"
            );
        }

        // Crear sesión
        if (
            session_status()
            ===
            PHP_SESSION_NONE
        ) {

            session_start();
        }

        $_SESSION[
        "usuario"
        ] = [

            "id" =>
                $usuario["id"],

            "nombre_usuario" =>
                $usuario[
                "nombre_usuario"
                ],

            "rol_id" =>
                $usuario[
                "rol_id"
                ]
        ];

        header(
            "Location: ?controller=home&method=index"
        );

        exit();
    }

    public function logout()
    {
        if (
            session_status()
            ===
            PHP_SESSION_NONE
        ) {

            session_start();
        }

        session_destroy();

        header(
            "Location: ?controller=home&method=index"
        );

        exit();
    }
}