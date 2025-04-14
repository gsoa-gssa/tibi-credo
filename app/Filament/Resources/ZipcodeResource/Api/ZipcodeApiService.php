<?php
namespace App\Filament\Resources\ZipcodeResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\ZipcodeResource;
use Illuminate\Routing\Router;


class ZipcodeApiService extends ApiService
{
    protected static string | null $resource = ZipcodeResource::class;

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
