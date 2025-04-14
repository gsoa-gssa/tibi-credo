<?php
namespace App\Filament\Resources\CommuneResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Commune;

/**
 * @property Commune $resource
 */
class CommuneTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
