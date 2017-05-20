<?php

namespace [[appns]]Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * [[appns]]Models\[[model_uc]]
 *
[[foreach:columns]]
 * @property [[i.doc_type]] $[[i.name]]

[[endforeach]]
 * @mixin \Eloquent
 */
class [[model_uc]] extends Model
{
    public $timestamps = false;
    protected $fillable = [[[foreach:columns]]'[[i.name]]', [[endforeach]]];

    public function rules($method, $id = false) {
        switch($method) {
            case 'GET':
            case 'DELETE':
                return [];
                break;
            case 'POST':
                return [
                [[foreach:columns]]
    '[[i.name]]' => '[[i.rules_create]]',
                [[endforeach]]];
                break;
            case 'PUT':
            case 'PATCH':
                return [
                [[foreach:columns]]
    '[[i.name]]' => "[[i.rules_update]]",
                [[endforeach]]];
                break;
            default:
                break;
        }
    }

    public function messages() {
        return [
        [[foreach:rules_messages]]
    '[[i.key]]' => '[[i.value]]',
        [[endforeach]]];
    }

    /**
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function validator($request, $id = false) {
        return Validator::make(
            $request->all(),
            $this->rules($request->method(), $id),
            $this->messages()
        );
    }

}
