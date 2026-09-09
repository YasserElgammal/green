<?php

namespace Tests\Web\Controllers;

use PHPUnit\Framework\TestCase;
use YasserElgammal\Green\Application;
use YasserElgammal\Green\ErrorHandling\GreenErrorKernel;
use YasserElgammal\Green\View\View;

abstract class BaseWebTestCase extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 3));
        }

        $this->app = new Application();
        $app = $this->app;
        require BASE_PATH . '/routes/web.php';
        View::init(dirname(__DIR__, 3) . '/views');
    }

    protected function tearDown(): void
    {
        $this->app->make(GreenErrorKernel::class)->unregister();

        parent::tearDown();
    }
}
