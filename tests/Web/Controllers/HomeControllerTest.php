<?php

namespace Tests\Web\Controllers;

use App\Controllers\Web\HomeController;
use PHPUnit\Framework\TestCase;
use YasserElgammal\Green\View\View;

class HomeControllerTest extends BaseWebTestCase
{
    private HomeController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new HomeController();
    }

    public function testHomeReturnsRenderedHomeView(): void
    {
        $response = $this->controller->home();

        $this->assertIsString($response);
        $this->assertNotEmpty($response);
        $this->assertStringContainsString('Green Framework', $response);
    }
}
