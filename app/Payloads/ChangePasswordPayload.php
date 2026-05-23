<?php

namespace App\Payloads;

use YasserElgammal\Green\Http\Payload;
use Respect\Validation\Validator as v;

class ChangePasswordPayload extends Payload
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $newPassword = $this->input('password');

        return [
            'current_password' => v::stringType()->notEmpty(),
            'password'         => v::stringType()->length(6, null)->notEmpty(),
            'confirm_password' => v::stringType()->identical($newPassword)->notEmpty(),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'confirm_password' => t('profile.validation.confirm_password_match') ?: 'The password confirmation does not match.',
        ];
    }
}
