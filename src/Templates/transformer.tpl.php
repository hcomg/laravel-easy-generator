<?php

namespace [[appns]]Transformers;


use [[appns]]Models\[[model_uc]];
use League\Fractal\TransformerAbstract;

class [[controller_name]]Transformer extends TransformerAbstract
{
    public function transform([[model_uc]] $[[model_singular]]) {
        return [
[[foreach:columns]]
            '[[i.name]]' => $[[model_singular]]->[[i.name]],
[[endforeach]]
        ];
    }
}
