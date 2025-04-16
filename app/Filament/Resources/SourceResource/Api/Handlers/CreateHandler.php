<?php
namespace App\Filament\Resources\SourceResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\SourceResource;
use App\Filament\Resources\SourceResource\Api\Requests\CreateSourceRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = SourceResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Source
     *
     * @param CreateSourceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateSourceRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}