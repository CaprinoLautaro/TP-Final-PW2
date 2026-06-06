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
        Log::info("UserController::registrarUsuario - formulario");
        $this->renderer->render("registroView", []);
    }
        public function procesarRegistroUsuario()
    {
        $nombre        = $this->request->post('nombre-completo');
        $nacimiento    = $this->request->post('anio-nacimiento');
        $contrasenia   = $this->request->post('contrasenia');
        $nombreUsuario = $this->request->post('user-name');
        $longitud      = $this->request->post('longitud');
        $latitud       = $this->request->post('latitud');
        $ciudad        = 'hola'; // $this->request->post('ciudad');
        $sexo          = $this->request->post('sexo');
        $pais          = 3; // $this->request->post('pais_id');  a partir del nombre voy a necesitar encontrar el id_pais en la db
        $email         = $this->request->post('email');
        $foto          = $this->procesarImagen();
        $token         = $this->generarToken();

        $datos = [$nombre, $nacimiento, $pais, $ciudad, $latitud, $longitud, $sexo, $email, $contrasenia, $nombreUsuario, $foto, $token];

        // validar los datos

        $this->model->registrar($datos);
        Log::info("UserController::procesarRegistroUsuario - nombre=$nombre");
        Redirect::toIndex();
    }

    private function procesarImagen()
    {
        $nombre = $_FILES['foto']['name'];
        $ruta_tmp = $_FILES['foto']['tmp_name'];
        $tamanio = $_FILES['foto']['size'];
        $error = $_FILES['foto']['error'];

        // validar que llego sin errores
        if ($error !== UPLOAD_ERR_OK) {
            Log::error("UserController::procesarRegistroUsuario - LLego con errores");
            Redirect::toIndex();
        }

        // validar extension
        $extensiones_permitidas = ['jpg', 'png', 'jpeg'];
        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        if(!in_array($extension, $extensiones_permitidas)) {
            Log::error("UserController::procesarRegistroUsuario - Extension invalida");
            Redirect::toIndex();
        }

        // validar tamanio
        $max_tamanio = 2 * 1024 * 1024;
        if($tamanio > $max_tamanio) {
            Log::error("UserController::procesarRegistroUsuario - La imagen supera el tamaño valido");
            Redirect::toIndex();
        }

        // carpeta de destino
        $ruta_final = 'public/img/' . uniqid() . "." . $extension;

        // mover la carpeta
        move_uploaded_file($ruta_tmp, $ruta_final);

        return $ruta_final;
    }

    private function generarToken()
    {
        return random_int(10000, 99999);
    }
}