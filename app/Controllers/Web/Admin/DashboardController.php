<?php

namespace App\Controllers\Web\Admin;

use App\Middleware\AdminMiddleware;
use YasserElgammal\Green\Routing\Route;

class DashboardController extends BaseAdminController
{
    #[Route('GET', '/admin', [AdminMiddleware::class])]
    public function dashboard()
    {
        $statistics = new StatisticsController();

        return view('admin/dashboard', [
            'stats' => $statistics->dashboardStats(),
            'latestUsers' => $statistics->latestUsers(5),
            'latestPosts' => $statistics->latestPosts(5),
            'latestComments' => $statistics->latestComments(5),
        ]);
    }
}
