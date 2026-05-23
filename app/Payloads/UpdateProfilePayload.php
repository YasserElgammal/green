<?php

namespace App\Payloads;

use YasserElgammal\Green\Http\Payload;
use Respect\Validation\Validator as v;

class UpdateProfilePayload extends Payload
{
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $avatar = $this->files['avatar'] ?? null;
        if ($avatar && $avatar['error'] !== UPLOAD_ERR_NO_FILE) {
            $this->post['avatar'] = $avatar['tmp_name'];
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => v::stringType()->length(3, 255)->notEmpty(),
            'email' => v::email()->length(3, 255)->notEmpty(),
            'avatar' => v::optional(v::image()->mimetype('image/jpeg')),
        ];
    }
}
