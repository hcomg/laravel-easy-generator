<?php

namespace [[appns]]Transformers;


use [[appns]]Models\[[model_uc]];
use League\Fractal\TransformerAbstract;

class [[controller_name]]Transformer extends TransformerAbstract
{
    public function transform([[model_uc]] $[[model_singular]]) {
        return [
            'id' => (int) $[[model_singular]]->id,
            'title' => (string) $[[model_singular]]->title,
        ];
    }
}
