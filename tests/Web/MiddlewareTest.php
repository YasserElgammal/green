<?php

namespace Tests\Web;

use App\Middleware\GuestMiddleware;
use App\Middleware\LocaleMiddleware;
use App\Middleware\SessionAuthMiddleware;
use App\Middleware\TokenAuthMiddleware;
use App\Middleware\TrimStringsMiddleware;
use App\Middleware\ValidateSessionUserMiddleware;
use App\Middleware\ValidationExceptionMiddleware;
use Tests\TestCase;
use YasserElgammal\Green\Http\RedirectResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Http\ValidationException;

class MiddlewareTest extends TestCase
{
    public function testGuestIsRedirectedFromProfile(): void
    {
        $response = (new SessionAuthMiddleware())->handle(new Request(), fn () => new Response('ok'));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testAuthenticatedUserIsRedirectedFromLogin(): void
    {
        auth()->login($this->createUser());
        $response = (new GuestMiddleware())->handle(new Request(), fn () => new Response('ok'));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testLocaleMiddlewareUsesTheSessionLocale(): void
    {
        session()->put('locale', 'ar');

        (new LocaleMiddleware())->handle(new Request(), fn () => new Response('ok'));

        $this->assertSame('الرئيسية', t('layout.home'));
    }
    public function testTrimStringsMiddlewareTrimsNestedInput(): void
    {
        $request = new Request(['search' => '  green  '], ['name' => '  Demo User ', 'meta' => ['city' => ' Cairo  ', 'age' => 20]]);
        $response = (new TrimStringsMiddleware())->handle($request, function (Request $request): Response {
            $this->assertSame('green', $request->query['search']);
            $this->assertSame('Demo User', $request->post['name']);
            $this->assertSame('Cairo', $request->post['meta']['city']);
            $this->assertSame(20, $request->post['meta']['age']);
            return new Response('ok');
        });
        $this->assertSame('ok', $response->getContent());
    }

    public function testValidationMiddlewareReturnsJsonErrorsForApiRequests(): void
    {
        $request = new Request(server: ['REQUEST_URI' => '/api/profile']);
        $response = (new ValidationExceptionMiddleware())->handle($request, fn () => throw new ValidationException(['email' => ['Invalid email.']]));
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['Invalid email.'], $data['errors']['email']);
    }

    public function testValidationMiddlewareFlashesErrorsAndRedirectsForWebRequests(): void
    {
        $_SERVER['HTTP_REFERER'] = '/profile';
        try {
            $response = (new ValidationExceptionMiddleware())->handle(
                new Request(server: ['REQUEST_URI' => '/profile']),
                fn () => throw new ValidationException(['name' => ['Name is required.']]),
            );
        } finally {
            unset($_SERVER['HTTP_REFERER']);
        }
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/profile', $response->getHeader('Location'));
        $this->assertSame(['name' => ['Name is required.']], session()->getFlash('errors'));
    }

    public function testValidationMiddlewarePassesSuccessfulResponsesThrough(): void
    {
        $expected = new Response('ok', 201);
        $this->assertSame($expected, (new ValidationExceptionMiddleware())->handle(new Request(), fn () => $expected));
    }

    public function testTokenMiddlewareRejectsMissingAndInvalidTokens(): void
    {
        $middleware = new TokenAuthMiddleware();
        $missing = $middleware->handle(new Request(), fn () => new Response('unreachable'));
        $invalid = $middleware->handle(new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer invalid']), fn () => new Response('unreachable'));
        $this->assertSame(401, $missing->getStatusCode());
        $this->assertSame(401, $invalid->getStatusCode());
    }

    public function testTokenMiddlewareAttachesTheAuthenticatedUser(): void
    {
        $user = $this->createUser();
        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer ' . auth()->issueToken($user)]);
        $response = (new TokenAuthMiddleware())->handle($request, function (Request $request) use ($user): Response {
            $this->assertSame($user->id, $request->getAttribute('user')->id);
            return new Response('ok');
        });
        $this->assertSame('ok', $response->getContent());
    }

    public function testValidateSessionUserMiddlewareAllowsAValidLoginToContinue(): void
    {
        $user = $this->createUser();
        auth()->login($user);
        $response = (new ValidateSessionUserMiddleware())->handle(new Request(), fn () => new Response('ok'));
        $this->assertSame('ok', $response->getContent());
        $this->assertSame($user->id, auth()->user()->id);
    }
}
