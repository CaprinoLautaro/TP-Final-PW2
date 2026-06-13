<?php
class PreguntaController
{
    private $renderer;
    private $request;
    private $preguntaModel;

    public function __construct(
        $renderer,
        $request,
        $preguntaModel
    ) {
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
    }

    public function crear()
    {
        $categorias =
            $this->preguntaModel->obtenerCategorias();

        $this->renderer->render(
            "crearPregunta",
            [
                "categorias" => $categorias
            ]
        );
    }

    public function guardar()
    {
        session_start();

        $enunciado =
            trim($_POST['enunciado']);

        $categoriaId =
            (int) $_POST['categoria_id'];

        $correcta =
            $_POST['correcta'] ?? '';

        if (empty($correcta)) {
            die("Debe seleccionar una respuesta correcta.");
        }

        if (empty($enunciado)) {
            die("Debe ingresar una pregunta.");
        }

        if ($categoriaId <= 0) {
            die("Debe seleccionar una categoría.");
        }

        $usuarioId =
            $_SESSION['usuario']['id'];

        $preguntaId =
            $this->preguntaModel->crearPregunta(
                $enunciado,
                $categoriaId,
                $usuarioId
            );

        $this->preguntaModel->crearOpcion(
            $preguntaId,
            $_POST['opcion_a'],
            $correcta === 'A' ? 1 : 0,
            1
        );

        $this->preguntaModel->crearOpcion(
            $preguntaId,
            $_POST['opcion_b'],
            $correcta === 'B' ? 1 : 0,
            2
        );

        $this->preguntaModel->crearOpcion(
            $preguntaId,
            $_POST['opcion_c'],
            $correcta === 'C' ? 1 : 0,
            3
        );

        $this->preguntaModel->crearOpcion(
            $preguntaId,
            $_POST['opcion_d'],
            $correcta === 'D' ? 1 : 0,
            4
        );

        $_SESSION['mensaje_exito'] =
            "Tu pregunta fue enviada para revisión.";

        header("Location: ?controller=home&method=index");
        exit();
    }
}