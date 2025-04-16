<?php
namespace App\Filament\Resources\SourceResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Source;

/**
 * @property Source $resource
 */
class SourceTransformer extends JsonResource
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
