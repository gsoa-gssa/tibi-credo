<?php

namespace App\Filament\Resources\SheetResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSheetRequest extends FormRequest
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
			'label' => 'required|string|unique:sheets,label',
			'signatureCount' => 'required|integer',
			'commune_id' => 'required|integer',
			'source_id' => 'required|integer',
			'vox' => 'boolean'
		];
    }
}
