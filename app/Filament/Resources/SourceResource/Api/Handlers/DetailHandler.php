<?php

namespace App\Filament\Resources\SourceResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\SourceResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\SourceResource\Api\Transformers\SourceTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = SourceResource::class;


    /**
     * Show Source
     *
     * @param Request $request
     * @return SourceTransformer
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

        return new SourceTransformer($query);
    }
}
