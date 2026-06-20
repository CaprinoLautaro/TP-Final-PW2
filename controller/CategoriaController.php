<?php

class CategoriaController
{

    private $renderer;
    private $request;
    private $categoriaModel;

    public function __construct($renderer, $request, $categoriaModel)
    {
        $this->renderer = $renderer;
        $this->request = $request;
        $this->categoriaModel = $categoriaModel;
    }

    public function index()
    {
        $categorias = $this->categoriaModel->obtenerCategorias();
        $this->renderer->render('categoriasView', [
            'categorias' => $categorias
        ]);
    }

    public function crear()
    {
        $this->renderer->render('categoriaFormView', [
            'titulo' => 'Nueva categoría',
            'accion' => '?controller=categoria&method=guardar',
            'color' => '#6c5ce7'
        ]);
    }

    public function guardar()
    {
        $nombre = trim($_POST['nombre']);
        $color = $_POST['color'];

        $this->categoriaModel->crearCategoria($nombre, $color);

        header('Location: ?controller=categoria&method=index');
        exit();
    }

    public function editar()
    {
        $id = (int)$_GET['id'];
        $categoria = $this->categoriaModel->obtenerCategoriaPorId($id);
        $this->renderer->render('categoriaFormView', [
            'categoria' => $categoria,
            'titulo' => 'Editar Categoria',
            'accion' => '?controller=categoria&method=actualizar',
            'color' => $categoria['color']
        ]);
    }

    public function actualizar()
    {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre']);
        $color = $_POST['color'];

        $this->categoriaModel->actualizarCategoria($id, $nombre, $color);
        header('Location: ?controller=categoria&method=index');
        exit();
    }

    public function cambiarEstado()
    {
        $id = (int)$_POST['id'];
        $estado = (int)$_POST['estado'];

        $this->categoriaModel->cambiarEstadoCategoria($id, $estado);

        header('Location: ?controller=categoria&method=index');
        exit();
    }

    public function toggle()
    {
        $id = (int)$_GET['id'];

        $this->categoriaModel->cambiarEstadoCategoria($id);

        header('Location: ?controller=categoria&method=index');
        exit();
    }


}