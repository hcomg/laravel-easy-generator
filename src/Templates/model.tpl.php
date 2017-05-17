<?php

namespace [[appns]]Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * [[appns]]Models\Tag
 *
 * @property int $id
 * @property string $title
 * @method static \Illuminate\Database\Query\Builder|\[[appns]]Models\Tag whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\[[appns]]Models\Tag whereTitle($value)
 * @mixin \Eloquent
 */
class [[model_uc]] extends Model
{
    public $timestamps = false;
    protected $fillable = ['title'];

    public function rules($method, $id = false) {
        switch($method) {
            case 'GET':
            case 'DELETE':
                return [];
                break;
            case 'POST':
                return [
                    'title' => 'required|unique:tags'
                ];
                break;
            case 'PUT':
            case 'PATCH':
                return [
                    'title' => 'required|unique:' . $this->getTable() . ',title,' . $id
                ];
                break;
            default:
                break;
        }
    }

    public function messages() {
        return [
            'title.required' => '',
            'title.unique' => ''
        ];
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
