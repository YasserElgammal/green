<?php

namespace App\Payloads;

use Respect\Validation\Validator as v;
use YasserElgammal\Green\Http\Payload;

class DeleteAccountPayload extends Payload
{
    public function rules(): array
    {
        return ['password' => v::stringType()->notEmpty()];
    }
}
