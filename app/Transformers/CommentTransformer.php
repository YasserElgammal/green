<?php

namespace App\Transformers;

use YasserElgammal\Green\Database\Model;
use YasserElgammal\Green\Transformer\Transformer;

class CommentTransformer extends Transformer
{
    public function transform(Model $model): array
    {
        return [
            'id'         => (int) $model->id,
            'content'    => $model->content,
            'created_at' => $model->created_at,
        ];
    }

    protected function includes(): array
    {
        return [
            'likes'  => new LikeTransformer(),
            'author' => new UserTransformer(),
        ];
    }
}
