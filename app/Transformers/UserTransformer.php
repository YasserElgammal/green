<?php

namespace App\Transformers;

use YasserElgammal\Green\Database\Model;
use YasserElgammal\Green\Transformer\Transformer;

class UserTransformer extends Transformer
{
    public function transform(Model $model): array
    {
        return [
            'id'         => (int) $model->id,
            'name'       => $model->name,
            'email'      => $model->email,
            'created_at' => $model->created_at,
        ];
    }

    protected function includes(): array
    {
        return [
            'posts' => new PostTransformer(),
            'roles' => new RoleTransformer(),
        ];
    }
}
