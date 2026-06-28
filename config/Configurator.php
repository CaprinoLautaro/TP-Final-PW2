<?php

class Configurator
{

    private $config;

    public function __construct()
    {
        $this->config = parse_ini_file("config/config.ini");

    }
    public function getConfig()
    {
        return $this->config;
    }

    public function getHomeController()
    {
        return new homeController(
            $this->getRenderer(),
            new Request(),
            $this->getPartidaModel(),
            $this->getRankingModel()
        );
    }

    public function getAdminController() {
        $database = $this->getDatabase();
        $adminModel = new AdminModel($database);
        return new AdminController($adminModel, $this->getRenderer());
    }

    public function getRankingController()
    {
        return new RankingController(
            $this->getRenderer(),
            new Request(),
            $this->getRankingModel()
        );
    }

    public function getUserController()
    {
        return new UserController($this->getUserModel(), $this->getRenderer(), new Request());
    }

    private function getDatabase()
    {
        return new MyDatabase(
            $this->config['hostname'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database']
        );
    }

    private function getRenderer()
    {
        return new MustacheRenderer(
            __DIR__ . '/../view',
            $this->getDatabase()
        );
    }

    public function getRouter()
    {
        return new Router($this, 'home', 'index');
    }

    private function getUserModel()
    {
        return new UserModel($this->getDatabase());
    }

    public function getOrDefault($controllerName, $defaultControllerName)
    {
        $getter = 'get' . ucfirst($controllerName) . 'Controller';
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        $defaultGetter = 'get' . ucfirst($defaultControllerName) . 'Controller';
        return $this->{$defaultGetter}();
    }

    public function getLoginController()
    {
        return new LoginController(
            $this->getRenderer(),
            new Request(),
            $this->getUserModel()
        );
    }

    public function getPerfilController()
    {
        return new PerfilController(
            $this->getRenderer(),
            new Request(),
            $this->getPerfilModel(),
            $this->getPartidaModel()
        );
    }

    public function getPartidaController()
    {
        return new PartidaController(
            $this->getRenderer(),
            new Request(),
            $this->getPartidaModel(),
            $this->getPreguntaModel(),
            $this->getUserModel(),
            $this->getReporteModel()
        );
    }

    public function getPreguntaController()
    {
        return new PreguntaController(
            $this->getRenderer(),
            new Request(),
            $this->getPreguntaModel()
        );
    }

    private function getPartidaModel()
    {
        return new PartidaModel($this->getDatabase());

    }

    private function getPreguntaModel()
    {
        return new PreguntaModel(
            $this->getDatabase()
        );
    }

    private function getPerfilModel()
    {
        return new PerfilModel(
            $this->getDatabase()
        );
    }

    private function getRankingModel()
    {
        return new RankingModel($this->getDatabase());
    }

    public function getCategoriaController()
    {
        return new CategoriaController(
            $this->getRenderer(),
            new Request(),
            $this->getCategoriaModel()
        );
    }

    private function getCategoriaModel()
    {
        return new CategoriaModel($this->getDatabase());
    }

    private function getReporteModel()
    {
        return new ReporteModel($this->getDatabase());
    }

    public function getHabilitarPreguntaController()
    {
        return new HabilitarPreguntaController(
            $this->getRenderer(),
            $this->getPreguntaModel(),
            $this->getReporteModel()
        );
    }
}