<?php

namespace App\Payloads;

use YasserElgammal\Green\Http\Payload;
use Respect\Validation\Validator as v;

class DeleteAccountPayload extends Payload
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'password' => v::stringType()->notEmpty(),
        ];
    }
}
