<?php

namespace Tests\Web;

use App\Controllers\Web\HomeController;
use App\Controllers\Web\LangController;
use Tests\TestCase;

class ControllerTest extends TestCase
{
    public function testHomeRendersTheHomeView(): void
    {
        $this->assertStringContainsString('<!DOCTYPE html>', (new HomeController())->home());
    }

    public function testLanguageCanBeChangedOnlyToAnAllowedLocale(): void
    {
        $_SERVER['HTTP_REFERER'] = '/profile';
        try {
            $response = (new LangController())->switch('ar');
            $this->assertSame('ar', session()->get('locale'));
            $this->assertSame('/profile', $response->getHeader('Location'));
            (new LangController())->switch('unsupported');
            $this->assertSame('ar', session()->get('locale'));
        } finally {
            unset($_SERVER['HTTP_REFERER']);
        }
    }
}
