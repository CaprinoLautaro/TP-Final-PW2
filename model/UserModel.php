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
        $sql = "INSERT INTO usuarios (nombre_completo, anio_nacimiento, pais_id, ciudad, latitud, longitud, sexo, email, contrasenia, nombre_usuario, foto_perfil,token_validacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->database->execute($sql, $data) > 0;
    }

    public function activarCuenta($token)
    {
        $sql = "UPDATE usuarios SET activo = 1, token_validacion = NULL WHERE token_validacion = ?";
        return $this->database->execute($sql, $token) > 0;
    }
}