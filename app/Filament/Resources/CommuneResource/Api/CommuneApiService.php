<?php
namespace App\Filament\Resources\CommuneResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\CommuneResource;
use Illuminate\Routing\Router;


class CommuneApiService extends ApiService
{
    protected static string | null $resource = CommuneResource::class;

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
