<?php

class CategoriaModel
{

    private $database;
    public function __construct($database)
    {
        $this->database = $database;
    }
    public function obtenerCategorias()
    {
        return $this->database->query(
            "SELECT id, nombre, color, activa
         FROM categorias
         ORDER BY nombre"
        );
    }

    public function crearCategoria($nombre, $color)
    {
        return $this->database->execute(
            "INSERT INTO categorias (nombre, color) VALUES (?, ?)",
            [$nombre, $color]
        );

    }

    public function obtenerCategoriaPorId($id)
    {
        $resultado = $this->database->query(
            "SELECT * FROM categorias WHERE id = ?",
            [$id]
        );
        return $resultado[0] ?? null;
    }

    public function actualizarCategoria($id, $nombre, $color)
    {
        return $this->database->execute(
            "UPDATE categorias SET nombre = ?, color = ? WHERE id = ?",
            [$nombre, $color, $id]
        );
    }

    public function cambiarEstadoCategoria($id)
    {
        $categoria = $this->obtenerCategoriaPorId($id);

        $estadoActual = (int) $categoria['activa'];

        $nuevoEstado = $estadoActual === 1 ? 0 : 1;

        return $this->database->execute(
            "UPDATE categorias SET activa = ? WHERE id = ?",
            [$nuevoEstado, $id]
        );
    }
}