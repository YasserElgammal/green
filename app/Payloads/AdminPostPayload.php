<?php

namespace App\Payloads;

use App\Enums\PostStatus;
use Respect\Validation\Validator as v;
use YasserElgammal\Green\Http\Payload;

class AdminPostPayload extends Payload
{
    protected function prepareForValidation(): void
    {
        $this->post['status'] = PostStatus::fromRequest($this->input('status'))->value;
    }

    public function rules(): array
    {
        return [
            'title' => v::stringType()->length(3, 255)->notEmpty(),
            'body' => v::stringType()->length(3, null)->notEmpty(),
            'user_id' => v::intVal()->positive(),
            'status' => v::in(PostStatus::values(), true),
        ];
    }
}
