<?php

namespace [[appns]]Http\Controllers;

use [[appns]]Models\[[model_uc]];
use [[appns]]Transformers\[[model_uc]]Transformer;
use Dingo\Api\Routing\Helpers;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use [[appns]]Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;

class [[controller_name]]Controller extends Controller
{
    use Helpers;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $[[model_plural]] = [[model_uc]]::orderBy('id', 'desc')->paginate(10);
        return $this->response->paginator($[[model_plural]], new [[model_uc]]Transformer());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $[[model_singular]] = new [[model_uc]]();

        $validator = $[[model_singular]]->validator($request);
        if ($validator->fails()) {
            return Response::json($validator->errors(), 400);
        }

        $[[model_singular]]->fill($request->all());
        try {
            $[[model_singular]]->save();
        } catch (QueryException $exception) {
            return Response::json([
                'message' => $exception->getMessage()
            ], 500);
        }
        return $this->response->item($[[model_singular]], new [[model_uc]]Transformer());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $[[model_singular]] = [[model_uc]]::findOrFail($id);
        return $this->response->item($[[model_singular]], new [[model_uc]]Transformer());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        /**
         * @var Tag $[[model_singular]]
         */
        $[[model_singular]] = [[model_uc]]::findOrFail($id);

        $validator = $[[model_singular]]->validator($request, $id);
        if ($validator->fails()) {
            return Response::json($validator->errors(), 400);
        }

        $[[model_singular]]->fill($request->all());
        try {
            $[[model_singular]]->save();
        } catch (QueryException $exception) {
            return Response::json([
                'message' => $exception->getMessage()
            ], 500);
        }
        return $this->response->item($[[model_singular]], new [[model_uc]]Transformer());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Dingo\Api\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            [[model_uc]]::whereIn('id', explode(',', $id))->delete();
        } catch (QueryException $exception) {
            return Response::json([
                'message' => $exception->getMessage()
            ], 500);
        }
        return $this->response->noContent();
    }
}
