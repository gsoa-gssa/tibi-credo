<?php

namespace App\Filament\Resources\SheetResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\SheetResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\SheetResource\Api\Transformers\SheetTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = SheetResource::class;


    /**
     * Show Sheet
     *
     * @param Request $request
     * @return SheetTransformer
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

        return new SheetTransformer($query);
    }
}
