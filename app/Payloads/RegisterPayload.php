<?php

namespace App\Payloads;

use Respect\Validation\Validator as v;
use YasserElgammal\Green\Http\Payload;

class RegisterPayload extends Payload
{
    public function rules(): array
    {
        return [
            'name' => v::stringType()->length(2, 100)->notEmpty(),
            'email' => v::email()->length(3, 255)->notEmpty(),
            'password' => v::stringType()->length(8, null)->notEmpty(),
        ];
    }
}
