<?php

namespace App\Filament\Resources\CommuneResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommuneRequest extends FormRequest
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
			'officialId' => 'required',
			'address' => 'required',
			'email' => 'required',
			'website' => 'required',
			'lang' => 'required',
			'phone' => 'required'
		];
    }
}
