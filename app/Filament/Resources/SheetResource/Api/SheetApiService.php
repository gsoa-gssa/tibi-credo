<?php
namespace App\Filament\Resources\SheetResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\SheetResource;
use Illuminate\Routing\Router;


class SheetApiService extends ApiService
{
    protected static string | null $resource = SheetResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];

    }
}
