<?php

namespace Tests\Web\Controllers;

use App\Controllers\Web\Admin\DashboardController;
use Tests\TestCase;
use YasserElgammal\Green\View\View;

class AdminControllerTest extends TestCase
{
    private DashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        View::init(dirname(__DIR__, 3) . '/views');
        $this->seed();
        $this->controller = new DashboardController();
    }

    public function testDashboardReturnsRenderedAdminView(): void
    {
        $response = $this->controller->dashboard();

        $this->assertIsString($response);
        $this->assertStringContainsString('Admin Dashboard', $response);
        $this->assertStringContainsString('Total Users', $response);
        $this->assertStringContainsString('test@example.com', $response);
    }
}
