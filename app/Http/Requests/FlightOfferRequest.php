<?php

namespace App\Http\Requests;

use App\Exceptions\CustomException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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
            'departure_date' => 'bail|date|required',
            'arrival_date' => 'bail|sometimes',
            'flight_class' => 'bail|required|'.Rule::in(['economy', 'premium_economy', 'business', 'first']),
            'no_of_infant' => 'bail|required|integer|min:0',
            'no_of_children' => 'bail|required|integer|min:0',
            'no_of_adult' => 'bail|required|integer|min:1',
            'trip_type' => 'bail|required|'.Rule::in(['one', 'round']),
            'stop_over' => 'bail|required|boolean',
            'search_id' => 'bail|required|unique:flight_offers,search_id',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key);
        $data['flight_class'] = strtoupper($data['flight_class']);
        $data['stop_over'] = $data['stop_over'] == true ? 'false' : 'true';
        $total_passenger = $data['no_of_infant'] + $data['no_of_children'] + $data['no_of_adult'];
        throw_if($total_passenger > 9, CustomException::class, 'Total passengers can only be 9');
        throw_if($data['no_of_adult'] < $data['no_of_infant'], CustomException::class, 'No of Infants can\'t be greater than adult');
        if ($data['trip_type'] == 'round' && blank($data['arrival_date'])) {
            throw new CustomException('Round-trip requires an arrival-or-return date');
        }

        if ($data['trip_type'] == 'one' && filled($data['arrival_date'])) {
            throw new CustomException('Only round-trip can have arrival/return date');
        }

        $departureDate = Carbon::parse($data['departure_date']);
        $arrivalDate = Carbon::parse($data['arrival_date']);
        $today = today();

        throw_if($departureDate->lessThan($today), CustomException::class, 'Departure date must be today or a date in the future');
        throw_if($arrivalDate->lessThan($today), CustomException::class, 'Arrival date must be today or a date in the future');

        return $data;
    }
}
