<?php

class LoginController
{

    private $renderer;
    private $request;
    private $usuarioModel;

    public function __construct($renderer, $request, $usuarioModel)
    {
        $this->renderer = $renderer;
        $this->request = $request;
        $this->usuarioModel = $usuarioModel;
    }

    public function index()
    {
        $this->renderer->render("login");
    }

    public function autenticar()
    {

        $nombreUsuario = $this->request->post("nombre_usuario");
        $contrasenia = $this->request->post("contrasenia");

        $usuario = $this->usuarioModel->buscarPorNombreUsuario($nombreUsuario);

        /*      if(!$usuario){
                 echo "Usuario no encontrado";
                 return;
             }

             if(!$usuario["activo"]){
                 echo "La cuenta aún no ha sido activada";
             }

             if(!password_verify(
                 $contrasenia,
                 $usuario["contrasenia"]
             )){
                 echo "Contraseña incorrecta";
                 return;
             }
             header("Location: ?controller=home&method=index");
             exit();*/

        if ($contrasenia !== $usuario["contrasenia"]) {
            echo "Contraseña incorrecta";
            return;
        }

        header("Location: ?controller=home&method=index");
        exit();
    }


}