<?php

require_once(
    __DIR__ .
    '/../vendor/autoload.php'
);

class MustacheRenderer
{
    private $mustache;
    private $database;  // agregás esto

    public function __construct(
        $viewsFolder,
        $database = null  // opcional para no romper nada
    ) {
        $this->database = $database;  // agregás esto

        $this->mustache =
            new Mustache_Engine([
                'loader' =>
                    new Mustache_Loader_FilesystemLoader(
                        $viewsFolder
                    ),
                'partials_loader' =>
                    new Mustache_Loader_FilesystemLoader(
                        $viewsFolder
                    ),
            ]);
    }

    public function render(
        $viewName,
        $data = []
    ) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuario = $_SESSION['usuario'] ?? null;

        $fotoPerfil = '';

        if ($usuario && $this->database) {
            $resultado = $this->database->query(
                "SELECT foto_perfil FROM usuarios WHERE id = ?",
                [$usuario['id']]
            );
            $fotoPerfil = $resultado[0]['foto_perfil'] ?? '';
        }

        $headerData = [
            'logueado'      => !empty($usuario),
            'nombre_usuario' => $usuario['nombre_usuario'] ?? '',
            'inicial'       => strtoupper(substr($usuario['nombre_usuario'] ?? '', 0, 1)),
            'foto_perfil'   => $fotoPerfil,
        ];

        $data = array_merge($data, $headerData);

        $template = $this->mustache->loadTemplate($viewName);

        echo $template->render($data);
    }
}