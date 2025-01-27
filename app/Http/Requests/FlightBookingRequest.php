<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use App\Exceptions\CustomException;

class FlightBookingRequest extends FormRequest
{
    public function rules()
    {
        return [
            'offer_id' => ['required', 'uuid', 'bail'],
            'carrier' => ['required', 'string', 'bail'],
            'passengers' => ['required', 'array', 'min:1', 'bail'],
            'passengers.*.id' => ['required', 'integer', 'bail'],
            'passengers.*.date_of_birth' => ['required', 'date', 'bail'],
            'passengers.*.passenger_type' => ['required', 'string', 'in:ADULT,CHILD,INFANT', 'bail'],
            'passengers.*.gender' => ['required', 'string', 'in:MALE,FEMALE', 'bail'],
            'passengers.*.name.first_name' => ['required', 'string', 'bail'],
            'passengers.*.name.last_name' => ['required', 'string', 'bail'],
            'passengers.*.contact.email_address' => ['required', 'email', 'bail'],
            'passengers.*.contact.phones' => ['required', 'array', 'min:1', 'bail'],
            'passengers.*.contact.phones.*.device_type' => ['required', 'string', 'in:MOBILE,LANDLINE,FAX', 'bail'],
            'passengers.*.contact.phones.*.country_calling_code' => ['required', 'string', 'bail'],
            'passengers.*.contact.phones.*.number' => ['required', 'string', 'regex:/^\+?[0-9]+$/', 'bail'],
            'passengers.*.documents' => ['required', 'array', 'min:1', 'bail'],
            'passengers.*.documents.*.document_type' => ['required', 'string', 'in:PASSPORT,ID_CARD', 'bail'],
            'passengers.*.documents.*.number' => ['required', 'string', 'bail'],
            'passengers.*.documents.*.birth_place' => ['required', 'string', 'bail'],
            'passengers.*.documents.*.issuance_location' => ['required', 'string', 'bail'],
            'passengers.*.documents.*.issuance_date' => ['required', 'date', 'bail'],
            'passengers.*.documents.*.expiry_date' => ['required', 'date', 'bail'],
            'passengers.*.documents.*.issuance_country' => ['required', 'string', 'bail'],
            'passengers.*.documents.*.validity_country' => ['required', 'string', 'bail'],
            'passengers.*.documents.*.nationality' => ['required', 'string', 'bail'],
            'passengers.*.documents.*.holder' => ['required', 'boolean', 'bail'],
        ];
    }

    public function validateAge($type, $departure_date, $dob)
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

    public function passedValidation()
    {
        $data = parent::validated();
        $passengers = $data['passengers'] ?? [];  // Ensure passengers is always an array
        $itineraries = $data['itineraries'] ?? []; // Ensure itineraries is set
        $departure_date = Carbon::parse($itineraries[0]['segments'][0]['departure']['at'] ?? now()); // Default to current time if not set

        foreach ($passengers as $psg) {
            $psg_type = $psg['passenger_type'] ?? null;
            $dob = $psg['date_of_birth'] ?? null;

            // Validate if $psg_type or $dob is missing
            if ($psg_type && $dob) {
                $this->validateAge($psg_type, $departure_date, $dob);
            } else {
                throw new CustomException('Passenger type or date of birth is missing.');
            }
        }
    }
}
