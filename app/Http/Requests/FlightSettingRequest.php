<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlightSettingRequest extends FormRequest
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
            'company_name' => 'bail|required',
            'company_phone' => 'bail|required|numeric',
            'addressee_first_name' => 'bail|required',
            'addressee_last_name' => 'bail|required',
            'country_calling_code' => 'bail|required|numeric',
            'email_address_for_amadeus' => 'bail|required|email',
            'company_address' => 'bail|required',
            'postal_code' => 'bail|required|numeric',
            'city_name' => 'bail|required',
            'country_code' => 'bail|required',
            'delay' => 'bail|numeric|required',
            'point_bonus_percentage' => 'bail|numeric|required',
            'flight_ticket_option' => 'bail|required|'.Rule::in(['CONFIRM', 'DELAY_TO_QUEUE', 'DELAY_TO_CANCEL']),
        ];
    }
}
