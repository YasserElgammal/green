<?php

/**
 * CSRF Protection Configuration
 *
 * These settings control the Cross-Site Request Forgery (CSRF) protection
 * provided by the Green Core CsrfMiddleware. Every state-changing request
 * (POST, PUT, PATCH, DELETE) going through the web middleware stack will
 * be validated automatically.
 *
 * @see \YasserElgammal\Green\Security\Csrf\CsrfConfig
 * @see \YasserElgammal\Green\Http\Middleware\CsrfMiddleware
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable CSRF Protection
    |--------------------------------------------------------------------------
    |
    | Set to false to disable CSRF verification globally. This should only be
    | done in very specific testing scenarios — never in production.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Token Time-To-Live (seconds)
    |--------------------------------------------------------------------------
    |
    | Each generated token expires after this many seconds. The default of
    | 1800 (30 minutes) balances security with usability for typical forms.
    |
    */
    'ttl' => 1800,

    /*
    |--------------------------------------------------------------------------
    | Maximum Active Tokens per Session
    |--------------------------------------------------------------------------
    |
    | Limits the number of active CSRF tokens stored in a single session.
    | When exceeded, the oldest tokens are automatically pruned.
    |
    */
    'max_tokens' => 50,

    /*
    |--------------------------------------------------------------------------
    | Session Storage Key
    |--------------------------------------------------------------------------
    |
    | The key under which all active CSRF tokens are stored in the session.
    |
    */
    'session_key' => '_csrf_tokens',

    /*
    |--------------------------------------------------------------------------
    | Form Input Names
    |--------------------------------------------------------------------------
    |
    | The hidden input names used by {{ csrf_field() }} in Twig forms.
    | The middleware reads these from the POST body during validation.
    |
    */
    'id_input'    => '_csrf_id',
    'token_input' => '_csrf_token',

    /*
    |--------------------------------------------------------------------------
    | AJAX Header Names
    |--------------------------------------------------------------------------
    |
    | For AJAX/fetch requests that cannot submit a form body, the middleware
    | also checks these request headers as a fallback.
    |
    | Example (JavaScript):
    |   fetch('/posts', {
    |       method: 'POST',
    |       headers: {
    |           'X-CSRF-ID': csrfId,
    |           'X-CSRF-TOKEN': csrfToken,
    |           'Content-Type': 'application/json',
    |       },
    |       body: JSON.stringify({ title: 'Hello' }),
    |   });
    |
    */
    'id_header'    => 'X-CSRF-ID',
    'token_header' => 'X-CSRF-TOKEN',

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | URIs listed here are excluded from CSRF verification. Use a trailing
    | wildcard (*) to match entire path prefixes (e.g. '/api/public/*').
    |
    | Typical exclusions: payment webhooks, third-party callbacks, and
    | public API endpoints that rely on token-based auth instead.
    |
    */
    'except' => [
        '/webhooks/*',
        '/api/public/*',
    ],

];
