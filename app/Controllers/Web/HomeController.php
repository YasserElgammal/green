<?php

namespace App\Controllers\Web;

use YasserElgammal\Green\Routing\Route;

class HomeController
{
    #[Route('GET', '/')]
    public function home()
    {
        return view('home', ['title' => 'Green Framework']);
    }
}
