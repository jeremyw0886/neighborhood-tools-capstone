<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Tool;

class HomeController extends BaseController
{
    public function index(): void
    {
        $this->render('home/index', [
            'title'       => 'NeighborhoodTools — Share Tools, Build Community',
            'heroPage'    => true,
            'css'         => ['home'],
            'nearbyMembers' => [],
            'featuredTools' => Tool::getFeatured(6),
            'topMembers'    => [],
        ]);
    }
}
