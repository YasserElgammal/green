<?php

namespace App\Transformers;

use YasserElgammal\Green\Database\Model;
use YasserElgammal\Green\Transformer\Transformer;

class RoleTransformer extends Transformer
{
    public function transform(Model $model): array
    {
        return [
            'id'   => (int) $model->id,
            'name' => $model->name,
        ];
    }
}
