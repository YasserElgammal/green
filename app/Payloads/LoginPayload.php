<?php

namespace App\Payloads;

use Respect\Validation\Validator as v;
use YasserElgammal\Green\Http\Payload;

class LoginPayload extends Payload
{
    public function rules(): array
    {
        return [
            'email' => v::email()->notEmpty(),
            'password' => v::stringType()->notEmpty(),
        ];
    }
}
