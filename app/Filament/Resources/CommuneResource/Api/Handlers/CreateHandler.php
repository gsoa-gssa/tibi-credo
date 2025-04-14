<?php
namespace App\Filament\Resources\CommuneResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\CommuneResource;
use App\Filament\Resources\CommuneResource\Api\Requests\CreateCommuneRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = CommuneResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Commune
     *
     * @param CreateCommuneRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateCommuneRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}