<?php

namespace App\Filament\Resources\ZipcodeResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZipcodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'name' => 'required',
			'code' => 'required',
			'commune_id' => 'required',
			'number_of_dwellings' => 'required'
		];
    }
}
