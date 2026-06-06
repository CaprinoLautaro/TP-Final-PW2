<?php

require_once(
    __DIR__ .
    '/../vendor/autoload.php'
);

class MustacheRenderer
{
    private $mustache;

    public function __construct(
        $viewsFolder
    ) {

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

        if (
            session_status()
            ===
            PHP_SESSION_NONE
        ) {

            session_start();
        }

        $usuario =
            $_SESSION[
            'usuario'
            ] ?? null;

        $headerData = [

            'logueado' =>
                !empty(
                $usuario
                ),

            'nombre_usuario' =>
                $usuario[
                'nombre_usuario'
                ] ?? '',

            'inicial' =>
                strtoupper(
                    substr(
                        $usuario[
                        'nombre_usuario'
                        ] ?? '',
                        0,
                        1
                    )
                )
        ];

        $data =
            array_merge(
                $data,
                $headerData
            );

        $template =
            $this->mustache
                ->loadTemplate(
                    $viewName
                );

        echo $template->render(
            $data
        );
    }
}