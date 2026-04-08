<?php

namespace Tests\Web\Controllers;

use PHPUnit\Framework\TestCase;
use YasserElgammal\Green\View\View;

abstract class BaseWebTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        View::init(dirname(__DIR__, 3) . '/views');
    }
}
