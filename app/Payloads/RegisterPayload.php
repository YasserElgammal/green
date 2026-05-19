<?php

namespace App\Payloads;

use YasserElgammal\Green\Http\Payload;
use Respect\Validation\Validator as v;

class RegisterPayload extends Payload
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'     => v::stringType()->length(3, 255)->notEmpty(),
            'email'    => v::email()->length(3, 255)->notEmpty(),
            'password' => v::stringType()->length(3, null)->notEmpty(),
        ];
    }

}
