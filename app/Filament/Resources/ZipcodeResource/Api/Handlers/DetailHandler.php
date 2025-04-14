<?php

namespace App\Filament\Resources\ZipcodeResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\ZipcodeResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\ZipcodeResource\Api\Transformers\ZipcodeTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = ZipcodeResource::class;


    /**
     * Show Zipcode
     *
     * @param Request $request
     * @return ZipcodeTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');
        
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new ZipcodeTransformer($query);
    }
}
