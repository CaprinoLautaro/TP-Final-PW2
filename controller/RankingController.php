<?php
class RankingController {

    private $renderer;
    private $request;
    private $model;

    public function __construct($renderer, $request, $model)
    {
        $this->renderer = $renderer;
        $this->request = $request;
        $this->model = $model;
    }

    public function index()
    {
        $ranking = $this->model->verRanking();
        $this->renderer->render('rankingView', [
            'jugadores' => $ranking
        ]);
    }

}