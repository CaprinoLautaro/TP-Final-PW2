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
}