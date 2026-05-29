<?php

class Configurator {

    private $config;

    public function __construct()
    {
        $this->config = parse_ini_file("config/config.ini");
    }

    public function getHomeController()
    {
        return new HomeController($this->getRenderer(), new Request());
    }

    public function getRankingController()
    {
        return new RankingController($this->getRenderer(), new Request());
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
        return new MustacheRenderer(__DIR__ . '/../view');
    }

    public function getRouter()
    {
        return new Router($this, 'home', 'index');
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
}