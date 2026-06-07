<?php

class UserController
{
    private $renderer;
    private $request;
    private $model;

    public function __construct($model, $renderer, $request)
    {
        $this->renderer = $renderer;
        $this->request  = $request;
        $this->model    = $model;
    }

    public function registrarUsuario()
    {
        Log::info(
            "UserController::registrarUsuario - formulario"
        );

        $this->renderer->render(
            "registroView",
            []
        );
    }



    public function procesarRegistroUsuario()
    {



        $email =
            trim(
                $this->request->post(
                    'email'
                )
            );

        $nombreUsuario =
            trim(
                $this->request->post(
                    'user-name'
                )
            );


        $nombre =
            trim(
                $this->request->post(
                    'nombre-completo'
                )
            );

        $nacimiento =
            $this->request->post(
                'anio-nacimiento'
            );

        $contrasenia =
            $this->request->post(
                'contrasenia'
            );

        $repetirContrasenia =
            $this->request->post(
                'repetir_contrasenia'
            );



        $longitud =
            $this->request->post(
                'longitud'
            );

        $latitud =
            $this->request->post(
                'latitud'
            );

        $ciudad =
            trim(
                $this->request->post(
                    'ciudad'
                )
            );

        $sexo =
            $this->request->post(
                'sexo'
            );

        $pais =
            $this->request->post(
                'pais_id'
            );


        if (
            $this->model->existeEmail(
                $email
            )
        ) {

            $this->renderer->render(
                "registroView",
                [
                    "error" =>
                        "Ese correo ya está registrado."
                ]
            );

            return;
        }

        if (
            $this->model->existeUsuario(
                $nombreUsuario
            )
        ) {

            $this->renderer->render(
                "registroView",
                [
                    "error" =>
                        "Ese nombre de usuario ya existe."
                ]
            );

            return;
        }


        if (
            empty($nombre) ||
            empty($nacimiento) ||
            empty($contrasenia) ||
            empty($repetirContrasenia) ||
            empty($nombreUsuario) ||
            empty($email) ||
            empty($sexo) ||
            empty($pais) ||
            empty($ciudad)
        ) {

            die(
            "Todos los campos son obligatorios."
            );
        }


        if (
            $contrasenia !==
            $repetirContrasenia
        ) {

            die(
            "Las contraseñas no coinciden."
            );
        }

        if (
            empty($latitud) ||
            empty($longitud)
        ) {

            die(
            "Seleccioná una ubicación en el mapa."
            );
        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            die(
            "Email inválido."
            );
        }



        $foto =
            $this->procesarImagen();



        $contraseniaHasheada =
            password_hash(
                $contrasenia,
                PASSWORD_DEFAULT
            );

        $token =
            $this->generarToken();

        $this->guardarTokenEnArchivo(
            $email,
            $token
        );


        $datos = [
            $nombre,
            $nacimiento,
            $pais,
            $ciudad,
            $latitud,
            $longitud,
            $sexo,
            $email,
            $contraseniaHasheada,
            $nombreUsuario,
            $foto,
            $token
        ];


        $resultado =
            $this->model->registrar(
                $datos
            );
        if ($resultado) {

            header(
                "Location: ?controller=user&method=mostrarValidacion"
            );

            exit();

        } else {

            die(
            "Error al registrar usuario."
            );
        }
    }

    public function mostrarValidacion()
    {
        $this->renderer->render(
            "validacionView",
            []
        );
    }

    public function validarToken()
    {
        $token =
            trim(
                $this->request->post(
                    'token'
                )
            );

        $resultado =
            $this->model->activarCuenta(
                $token
            );

        if ($resultado) {

            header("Location:?controller=login&method=index&success=1");
            exit();

        } else {

            $this->renderer->render(
                "validacionView",
                [
                    "error" =>
                        "Token inválido"
                ]
            );
        }
    }

    private function guardarTokenEnArchivo(
    $email,
    $token
)
{
    $contenido =
        "EMAIL: " .
        $email .
        PHP_EOL .
        "TOKEN: " .
        $token .
        PHP_EOL .
        "------------------" .
        PHP_EOL;

    file_put_contents(
        __DIR__ . "/../tokens.txt",
        $contenido,
        FILE_APPEND
    );
}

    private function procesarImagen()
    {
        $nombre =
            $_FILES['foto']['name'];

        $ruta_tmp =
            $_FILES['foto']['tmp_name'];

        $tamanio =
            $_FILES['foto']['size'];

        $error =
            $_FILES['foto']['error'];


        if (
            $error !==
            UPLOAD_ERR_OK
        ) {

            Log::error(
                "UserController::procesarRegistroUsuario - Error subiendo imagen"
            );

            die(
            "Error subiendo imagen."
            );
        }


        $extensionesPermitidas = [
            'jpg',
            'jpeg',
            'png'
        ];

        $extension =
            strtolower(
                pathinfo(
                    $nombre,
                    PATHINFO_EXTENSION
                )
            );

        if (
            !in_array(
                $extension,
                $extensionesPermitidas
            )
        ) {

            die(
            "Formato inválido. Solo JPG, JPEG o PNG."
            );
        }

        $maxTamanio =
            2 * 1024 * 1024;

        if (
            $tamanio >
            $maxTamanio
        ) {

            die(
            "La imagen supera 2MB."
            );
        }

        $rutaFinal =
            'public/img/' .
            uniqid() .
            "." .
            $extension;

        if (
            !move_uploaded_file(
                $ruta_tmp,
                $rutaFinal
            )
        ) {

            die(
            "No se pudo guardar la imagen."
            );
        }

        return $rutaFinal;
    }

    private function generarToken()
    {
        return bin2hex(
            random_bytes(32)
        );
    }
}