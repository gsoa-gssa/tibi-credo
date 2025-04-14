<?php
namespace App\Filament\Resources\ZipcodeResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ZipcodeResource;
use App\Filament\Resources\ZipcodeResource\Api\Requests\CreateZipcodeRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = ZipcodeResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Zipcode
     *
     * @param CreateZipcodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateZipcodeRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}