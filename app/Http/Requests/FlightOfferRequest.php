<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightOfferRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to' => 'bail|required|string',
            'from' => 'bail|required|string',

        ];
    }
}
