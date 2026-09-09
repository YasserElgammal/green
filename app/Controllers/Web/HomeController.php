<?php

namespace App\Controllers\Web;

use YasserElgammal\Green\Routing\Route;

class HomeController
{
    #[Route('GET', '/', name: 'home')]
    public function home()
    {
        return view('home', ['title' => 'Green Framework']);
    }
}
