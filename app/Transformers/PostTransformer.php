<?php

namespace App\Transformers;

use YasserElgammal\Green\Database\Model;
use YasserElgammal\Green\Transformer\Transformer;

class PostTransformer extends Transformer
{
    public function transform(Model $model): array
    {
        return [
            'id'         => (int) $model->id,
            'title'      => $model->title,
            'body'       => $model->body,
            'created_at' => $model->created_at,
        ];
    }

    protected function includes(): array
    {
        return [
            'comments' => new CommentTransformer(),
            'author'   => new UserTransformer(),
        ];
    }
}
