<?php

class PreguntaController
{
    private $renderer;
    private $request;
    private $preguntaModel;

    private $partidaModel;

    public function __construct(
        $renderer,
        $request,
        $preguntaModel,
        $partidaModel
    )
    {
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
        $this->partidaModel = $partidaModel;
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

    public function editarPreguntas()
    {
        $preguntas =
            $this->preguntaModel->obtenerTodasLasPreguntas();

        $this->renderer->render(
            "editarPregunta",
            [
                "preguntas" => $preguntas
            ]
        );
    }

    /* public function editarPregunta()
    {
        $preguntaId = $_GET['id'];
        $pregunta = $this->preguntaModel->obtenerPreguntaPorId($preguntaId);
        $opciones = $this->partidaModel->obtenerOpciones($preguntaId);
        $categorias = $this->preguntaModel->obtenerCategorias();

        $this->renderer->render(
            "preguntaFormView",
            [
                "pregunta" => $pregunta,
                "opciones" => $opciones,
                "categorias" => $categorias
            ]
        );
    } */

    public function editarPregunta()
    {
        $preguntaId = $_GET['id'];

        // 1. Obtener los datos crudos de los modelos
        $preguntaRaw = $this->preguntaModel->obtenerPreguntaPorId($preguntaId);
        // Como el modelo devuelve un array de filas, nos quedamos con la primera
        $pregunta = $preguntaRaw[0] ?? null;

        $opcionesRaw = $this->partidaModel->obtenerOpciones($preguntaId);
        $categoriasRaw = $this->preguntaModel->obtenerCategorias();

        if (!$pregunta) {
            die("La pregunta no existe.");
        }

        // Equivalencia de orden de la base de datos a letras para la vista
        $letrasAsignadas = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'];

        // 2. Formatear Categorías (saber cuál es la seleccionada actualmente)
        $categoriasFormateadas = [];
        foreach ($categoriasRaw as $cat) {
            $categoriasFormateadas[] = [
                "id" => $cat['id'],
                "nombre" => $cat['nombre'],
                "actual" => ($cat['id'] == $pregunta['categoria_id'])
            ];
        }

        // 3. Formatear Opciones (convertir orden 1-4 a letras A-D y verificar la correcta)
        $opcionesFormateadas = [];
        foreach ($opcionesRaw as $opc) {
            $letra = $letrasAsignadas[$opc['orden']] ?? 'A';

            // En tu PartidaModel ya tenés un método para saber si es correcta usando su ID
            $esCorrecta = $this->partidaModel->esOpcionCorrecta($opc['id']);

            $opcionesFormateadas[] = [
                "id" => $opc['id'],
                "letra" => $letra,
                "texto" => $opc['texto'],
                "es_correcta" => $esCorrecta
            ];
        }

        // 4. Renderizar pasando las estructuras limpias
        $this->renderer->render(
            "preguntaFormView",
            [
                "pregunta" => $pregunta,
                "categorias" => $categoriasFormateadas,
                "opciones" => $opcionesFormateadas
            ]
        );
    }


    public function guardar()
    {
        session_start();

        $enunciado =
            trim($_POST['enunciado']);

        $categoriaId =
            (int)$_POST['categoria_id'];

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

    public function actualizar()
    {
        // 1. Validar que vengan los datos obligatorios
        $preguntaId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $enunciado = isset($_POST['enunciado']) ? trim($_POST['enunciado']) : '';
        $categoriaId = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
        $correcta = $_POST['correcta'] ?? ''; // Vendrá 'A', 'B', 'C' o 'D'

        if ($preguntaId <= 0) {
            die("ID de pregunta inválido.");
        }
        if (empty($enunciado)) {
            die("Debe ingresar un enunciado para la pregunta.");
        }
        if ($categoriaId <= 0) {
            die("Debe seleccionar una categoría válida.");
        }
        if (empty($correcta)) {
            die("Debe seleccionar una respuesta correcta.");
        }

        // 2. Actualizar los datos básicos de la pregunta (Enunciado y Categoría)
        $this->preguntaModel->actualizarPregunta($preguntaId, $enunciado, $categoriaId);

        // 3. Actualizar cada una de las 4 opciones en la base de datos
        // Pasamos el ID de la pregunta, el texto nuevo, si es la correcta (1 o 0) y su número de orden (1 al 4)
        $this->preguntaModel->actualizarOpcion($preguntaId, $_POST['opcion_a'], $correcta === 'A' ? 1 : 0, 1);
        $this->preguntaModel->actualizarOpcion($preguntaId, $_POST['opcion_b'], $correcta === 'B' ? 1 : 0, 2);
        $this->preguntaModel->actualizarOpcion($preguntaId, $_POST['opcion_c'], $correcta === 'C' ? 1 : 0, 3);
        $this->preguntaModel->actualizarOpcion($preguntaId, $_POST['opcion_d'], $correcta === 'D' ? 1 : 0, 4);

        // 4. Setear mensaje de éxito en la sesión (opcional, como hacen en guardar)
        session_start();
        $_SESSION['mensaje_exito'] = "La pregunta fue modificada con éxito.";

        // 5. Redireccionar al listado de edición de preguntas
        header("Location: ?controller=pregunta&method=editarPreguntas");
        exit();
    }
}