<?php
namespace App\Filament\Resources\SourceResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\SourceResource;
use App\Filament\Resources\SourceResource\Api\Requests\FindSourceRequest;

class FindHandler extends Handlers {
    public static string | null $uri = '/find';
    public static string | null $resource = SourceResource::class;

    public static function getMethod()
    {
        return Handlers::GET;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    public function handler(FindSourceRequest $request)
    {
        $model = new (static::getModel());
        $sources = $model->where("code", $request->code)->get();

        if ($sources->isEmpty()) {
            return static::sendNotFoundResponse("Source with code $request->code not found");
        } else {
            return static::sendSuccessResponse($sources, "Source with code $request->code found");
        }
    }
}
