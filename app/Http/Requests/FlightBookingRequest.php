<?php

namespace App\Http\Requests;

use App\Exceptions\CustomException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class FlightBookingRequest extends FormRequest
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
        $rules = [
            'offer_id' => 'bail|required|exists:flight_offers,id',
            'passengers' => 'bail|array|required',
            'carrier' => 'bail|string|required',
            // 'payment_reference' => 'bail|required|exists:transactions,reference',
            // 'documents' => 'bail|array|required',
            // 'documents' => 'bail|array|required',
            // 'documents.*.document_type' => 'bail|required|in:PASSPORT,VISA',
            // 'documents.*.birth_place' => 'bail|required',
            // 'documents.*.issuance_location' => 'bail|required',
            // 'documents.*.issuance_date' => 'bail|date|required',
            // 'documents.*.number' => 'bail|numeric|required',
            // 'documents.*.expiry_date' => 'bail|date|required',
            // 'documents.*.issuance_country' => 'bail|required',
            // 'documents.*.validity_country' => 'bail|required',
            // 'documents.*.nationality' => 'bail|required',
            // 'document.*.holder' => 'bail|required|boolean',
        ];

        foreach ($this->input('passengers') as $key => $value) {
            $rules['passengers.*.id'] = 'bail|required';
            $rules['passengers.*.passenger_type'] = 'bail|required|'.Rule::in(['ADULT', 'CHILD', 'INFANT']);
            $rules['passengers.*.date_of_birth'] = 'bail|date|required';
            $rules['passengers.*.name'] = 'bail|array|required';
            $rules['passengers.*.name.first_name'] = 'bail|required';
            $rules['passengers.*.name.last_name'] = 'bail|required';
            $rules['passengers.*.gender'] = 'bail|required|'.Rule::in(['MALE', 'FEMALE']);
            $rules['passengers.*.contact'] = 'bail|required|array';
            $rules['passengers.*.contact.email_address'] = 'bail|required|email';
            $rules['passengers.*.contact.phones.*.device_type'] = 'bail|required|in:MOBILE,LANDLINE,FAX';
            $rules['passengers.*.contact.phones.*.country_calling_code'] = 'bail|required';
            $rules['passengers.*.contact.phones.*.number'] = 'bail|required';
            $rules['passengers.*.documents'] = 'bail|array|required';
            $rules['passengers.*.documents.*.document_type'] = 'bail|required|in:PASSPORT,VISA';
            $rules['passengers.*.documents.*.birth_place'] = 'bail|required';
            $rules['passengers.*.documents.*.issuance_location'] = 'bail|required';
            $rules['passengers.*.documents.*.issuance_date'] = 'bail|date|required';
            $rules['passengers.*.documents.*.number'] = 'bail|numeric|required';
            $rules['passengers.*.documents.*.expiry_date'] = 'bail|date|required';
            $rules['passengers.*.documents.*.issuance_country'] = 'bail|required';
            $rules['passengers.*.documents.*.validity_country'] = 'bail|required';
            $rules['passengers.*.documents.*.nationality'] = 'bail|required';
            $rules['passengers.*.documents.*.holder'] = 'bail|required|boolean';
        }

        return $rules;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'passengers.*.id.required' => 'The passenger id is required.',
            'passengers.*.passenger_type.required' => 'The passenger type is required.',
            'passengers.*.passenger_type.in' => 'The passenger type can any of this ADULT,CHILD,INFANT',
            'passengers.*.date_of_birth.required' => 'The passenger date of birth is required.',
            'passengers.*.date_of_birth.date' => 'The passenger date of birth must be a valid date',
            'passengers.*.email.required' => 'The passenger email is required',
            'passengers.*.email.email' => 'The passenger email is invalid',
            'passengers.*.contact.phones.*.device_type.required' => 'The passenger device type is required',
            'passengers.*.contact.phones.*.device_type.in' => 'Supported passenger device types are MOBILE,LANDLINE,FAX',
            'passengers.*.gender.required' => 'The passenger gender is required',
            'passengers.*.gender.in' => 'The passenger can either be a MALE  or FEMALE',
            'passengers.*.contact.phones.*.country_calling_code.required' => 'The passenger country calling code is required.',
            'passengers.*.contact.required' => 'The passenger country code is required.',
            'passengers.*.contact.phones.*.number.required' => 'Passenger phone number is required',
            'passengers.*.documents.*.document_type.in' => 'Document type not supported',
            'passengers.*.documents.*.issuance_date.date' => 'Document issuance date is not valid',
            'passengers.*.documents.*.expirty_date.date' => 'Document expiry date is not valid',

        ];
    }

    /**
     * @param  array<string,mixed>  $itineraries
     */
    public function validatePassengerAge(array $itineraries): void
    {
        $data = parent::validated();
        $passengers = $data['passengers']; //@phpstan-ignore-next-line
        $departure_date = Carbon::parse($itineraries[0]['segments'][0]['departure']['at']);
        foreach ($passengers as $psg) {

            $psg_type = $psg['passenger_type'];
            $dob = $psg['date_of_birth'];
            $this->validateAge($psg_type, $departure_date, $dob);
        }
    }

    private function validateAge(string $type, Carbon $departure_date, string $dob): void
    {
        $dob = Carbon::parse($dob);
        $age = $departure_date->diffInYears($dob);
        if ($type == 'ADULT' && $age < 12) {
            throw new CustomException('Adult passenger age should be from 12 and above');
        }

        if ($type == 'CHILD' && ($age < 2 || $age >= 12)) {
            throw new CustomException('Child passenger age should be from 3 - 11 years');
        }

        if ($type == 'INFANT' && $age > 2) {
            throw new CustomException('Infant passenger age should be from 0 - 2 years');
        }
    }
}
