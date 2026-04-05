<?php

namespace App\Transformers;

use YasserElgammal\Green\Database\Model;
use YasserElgammal\Green\Transformer\Transformer;

class LikeTransformer extends Transformer
{
    public function transform(Model $model): array
    {
        return [
            'id'         => (int) $model->id,
            'created_at' => $model->created_at,
        ];
    }

    protected function includes(): array
    {
        return [
            'user' => new UserTransformer(),
        ];
    }
}
