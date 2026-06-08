<?php

class PerfilModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buscarPorId($id)
    {
        $resultado = $this->database->query(
            "SELECT nombre_completo,
                    nombre_usuario,
                    email,
                    ciudad,
                    sexo,
                    foto_perfil,
                    anio_nacimiento,
                    latitud,
                    longitud
             FROM usuarios
             WHERE id = ?",
            [$id]
        );

        return $resultado[0] ?? null;
    }
    public function actualizarUsuario($id, $nombre, $ciudad, $sexo, $anio, $latitud, $longitud) {
        $sql = "UPDATE usuarios 
            SET nombre_completo = ?, 
                ciudad = ?, 
                sexo = ?, 
                anio_nacimiento = ?, 
                latitud = ?, 
                longitud = ? 
            WHERE id = ?";

        return $this->database->execute($sql, [
            $nombre,
            $ciudad,
            $sexo,
            $anio,
            $latitud,
            $longitud,
            $id
        ]);
    }
}