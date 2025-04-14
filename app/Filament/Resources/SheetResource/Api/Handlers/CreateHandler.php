<?php
namespace App\Filament\Resources\SheetResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\SheetResource;
use App\Filament\Resources\SheetResource\Api\Requests\CreateSheetRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = SheetResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Sheet
     *
     * @param CreateSheetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateSheetRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}