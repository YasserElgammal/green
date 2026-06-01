<?php

namespace App\Payloads;

use Respect\Validation\Validator as v;
use YasserElgammal\Green\Http\Payload;

class AdminStoreUserPayload extends Payload
{
    protected function prepareForValidation(): void
    {
        $this->post['is_admin'] = in_array($this->input('is_admin'), ['1', 1, true, 'on'], true) ? 1 : 0;
    }

    public function rules(): array
    {
        return [
            'name' => v::stringType()->length(3, 255)->notEmpty(),
            'email' => v::email()->length(3, 255)->notEmpty(),
            'password' => v::stringType()->length(3, null)->notEmpty(),
            'is_admin' => v::intVal()->between(0, 1),
        ];
    }
}
