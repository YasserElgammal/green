<?php

namespace App\Exceptions\Contracts;

use Throwable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

interface ErrorResponderInterface
{
    public function handle(Throwable $e, Request $request): Response;

    public function handleStatus(int $statusCode): Response;
}
