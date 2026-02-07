<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Tool;

class HomeController extends BaseController
{
    public function index(): void
    {
        try {
            $featuredTools = Tool::getFeatured(6);
        } catch (\Exception) {
            $featuredTools = [];
        }

        $this->render('home/index', [
            'title'       => 'NeighborhoodTools — Share Tools, Build Community',
            'heroPage'    => true,
            'css'         => ['home'],
            'nearbyMembers' => [],
            'featuredTools' => $featuredTools,
            'topMembers'    => [],
        ]);
    }
}
