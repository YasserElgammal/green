<?php

namespace App\Payloads;

use Respect\Validation\Validator as v;
use YasserElgammal\Green\Http\Payload;

class ChangePasswordPayload extends Payload
{
    public function rules(): array
    {
        return [
            'current_password' => v::stringType()->notEmpty(),
            'password' => v::stringType()->length(8, null)->notEmpty(),
            'confirm_password' => v::stringType()->identical($this->input('password'))->notEmpty(),
        ];
    }

    public function messages(): array
    {
        return ['confirm_password' => 'The password confirmation does not match.'];
    }
}
