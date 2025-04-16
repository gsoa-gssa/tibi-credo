<?php
namespace App\Filament\Resources\SourceResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\SourceResource;
use Illuminate\Routing\Router;


class SourceApiService extends ApiService
{
    protected static string | null $resource = SourceResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\FindHandler::class
        ];
    }
}
