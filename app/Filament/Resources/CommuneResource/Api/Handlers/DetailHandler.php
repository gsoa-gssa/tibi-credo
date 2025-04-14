<?php

namespace App\Filament\Resources\CommuneResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\CommuneResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\CommuneResource\Api\Transformers\CommuneTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = CommuneResource::class;


    /**
     * Show Commune
     *
     * @param Request $request
     * @return CommuneTransformer
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

        return new CommuneTransformer($query);
    }
}
