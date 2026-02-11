<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Account;
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

        try {
            $topMembers = Account::getTopMembers(3);
        } catch (\Exception) {
            $topMembers = [];
        }

        $this->render('home/index', [
            'title'       => 'NeighborhoodTools — Share Tools, Build Community',
            'heroPage'    => true,
            'nearbyMembers' => [],
            'featuredTools' => $featuredTools,
            'topMembers'    => $topMembers,
        ]);
    }
}
