<?php

namespace App\Controllers;

use App\Core\BaseController;

class HomeController extends BaseController
{
    public function index(): void
    {
        $this->render('home/index', [
            'title'       => 'NeighborhoodTools — Share Tools, Build Community',
            'heroPage'    => true,
            'nearbyMembers' => [],
            'featuredTools' => [],
            'topMembers'    => [],
        ]);
    }
}
