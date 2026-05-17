<?php

namespace App\Controllers\Web;

use YasserElgammal\Green\Http\RedirectResponse;
use YasserElgammal\Green\Routing\Route;
use YasserElgammal\Green\Translation\TranslatorManager;

class LangController
{
    #[Route('GET', '/lang/{locale}')]
    public function switch(string $locale): RedirectResponse
    {
        // Define allowed locales
        $allowed = ['en', 'ar'];

        if (in_array($locale, $allowed, true)) {
            session()->put('locale', $locale);
        }

        return redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
