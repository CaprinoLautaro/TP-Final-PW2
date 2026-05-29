
<?php
class HomeController {

    private $renderer;
    private $request;

    public function __construct($renderer, $request)
    {
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function index()
    {
        $this->renderer->render('homeView', []);
    }
}