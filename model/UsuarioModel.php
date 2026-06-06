<?php


class UsuarioModel
{

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buscarPorNombreUsuario($nombre_usuario)
    {
        $resultado = $this->database->query(
            "SELECT * FROM usuarios WHERE nombre_usuario = ?",
            [$nombre_usuario]
        );
        return $resultado[0] ?? null;
    }

    public function buscarPorMail($mail)
    {
        $filas = $this->database->query(
            "SELECT * FROM usuarios WHERE mail = ?",
            [$mail]
        );
        return !empty($filas) ? $filas[0] : null;
    }

    //funcion temporal hasta poder verificar la contraseña con hash

    public function autenticar()
    {


        die("Entré a autenticar");


        $nombreUsuario = $this->request->post("nombre_usuario");
        $contrasenia = $this->request->post("contrasenia");

        $usuario = $this->usuarioModel->buscarPorNombreUsuario($nombreUsuario);

        if (!$usuario) {
            echo "Usuario no encontrado";
            return;
        }

        if (!$usuario["activo"]) {
            echo "La cuenta aún no ha sido activada";
            return;
        }

        if ($contrasenia !== $usuario["contrasenia"]) {
            echo "Contraseña incorrecta";
            return;
        }

        header("Location: ?controller=home&method=index");
        exit();
    }
}