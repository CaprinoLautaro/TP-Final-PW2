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

    private function verificarLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['usuario'])) {
            header("Location: ?controller=login&method=index");
            exit();
        }
    }

    public function index()
    {
        $this->verificarLogin();

        $categorias = $this->categoriaModel->obtenerCategorias();
        $this->renderer->render('categoriasView', [
            'categorias' => $categorias
        ]);
    }

    public function crear()
    {
        $this->verificarLogin();

        $this->renderer->render('categoriaFormView', [
            'titulo' => 'Nueva categoría',
            'accion' => '?controller=categoria&method=guardar',
            'color' => '#6c5ce7'
        ]);
    }

    public function guardar()
    {
        $this->verificarLogin();

        $nombre = trim($_POST['nombre']);
        $color = $_POST['color'];

        $this->categoriaModel->crearCategoria($nombre, $color);

        header('Location: ?controller=categoria&method=index');
        exit();
    }

    public function editar()
    {
        $this->verificarLogin();

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
        $this->verificarLogin();

        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre']);
        $color = $_POST['color'];

        $this->categoriaModel->actualizarCategoria($id, $nombre, $color);
        header('Location: ?controller=categoria&method=index');
        exit();
    }

    public function cambiarEstado()
    {
        $this->verificarLogin();

        $id = (int)$_POST['id'];
        $estado = (int)$_POST['estado'];

        $this->categoriaModel->cambiarEstadoCategoria($id, $estado);

        header('Location: ?controller=categoria&method=index');
        exit();
    }

    public function toggle()
    {
        $this->verificarLogin();

        $id = (int)$_GET['id'];

        $this->categoriaModel->cambiarEstadoCategoria($id);

        header('Location: ?controller=categoria&method=index');
        exit();
    }


}