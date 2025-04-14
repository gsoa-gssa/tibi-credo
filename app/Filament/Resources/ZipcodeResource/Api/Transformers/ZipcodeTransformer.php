<?php
namespace App\Filament\Resources\ZipcodeResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Zipcode;

/**
 * @property Zipcode $resource
 */
class ZipcodeTransformer extends JsonResource
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
