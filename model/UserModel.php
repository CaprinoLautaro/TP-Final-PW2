<?php

class UserModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrar($data)
    {
        $sql = "INSERT INTO usuarios (
            nombre_completo,
            anio_nacimiento,
            pais_id,
            ciudad,
            latitud,
            longitud,
            sexo,
            email,
            contrasenia,
            nombre_usuario,
            foto_perfil,
            token_validacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->database->execute(
                $sql,
                $data
            ) > 0;
    }

    public function existeEmail($email)
    {
        $sql =
            "SELECT id
         FROM usuarios
         WHERE email = ?";

        $resultado =
            $this->database->query(
                $sql,
                [$email]
            );

        return !empty($resultado);
    }

    public function existeUsuario(
        $nombreUsuario
    )
    {
        $sql =
            "SELECT id
         FROM usuarios
         WHERE nombre_usuario = ?";

        $resultado =
            $this->database->query(
                $sql,
                [$nombreUsuario]
            );

        return !empty($resultado);
    }

    public function activarCuenta($token)
    {
        $sql = "UPDATE usuarios
                SET activo = 1,
                    token_validacion = NULL
                WHERE token_validacion = ?";

        return $this->database->execute(
                $sql,
                [$token]
            ) > 0;
    }

    public function buscarPorNombreUsuario($nombre_usuario)
    {
        $resultado = $this->database->query(
            "SELECT * FROM usuarios WHERE nombre_usuario = ?",
            [$nombre_usuario]
        );
        return $resultado[0] ?? null;
    }

    public function buscarPorMail(
        $mail
    )
    {
        $filas =
            $this->database->query(
                "SELECT *
             FROM usuarios
             WHERE email = ?",
                [$mail]
            );

        return
            !empty($filas)
                ? $filas[0]
                : null;
    }


}