<?php

namespace App\Controllers;

use YasserElgammal\Green\Routing\Route;

class WebController
{
    #[Route('GET', '/')]
    public function home()
    {
        return view('home', ['title' => 'Green Framework']);
    }
}
