<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'channel' => ['bail', 'required', Rule::in(['paystack', 'flutterwave'])],
            'deal_id' => ['bail', 'required', 'exists:flight_deals,id'],
            'booking_id' => ['bail', 'required', 'exists:book_deals,id'],
        ];
    }



    public function messages()
    {
        return [
            'channel.required' => 'Payment channel is required.',
            'channel.in' => 'The selected payment channel is invalid.',
            'deal_id.required' => 'Deal ID is required.',
            'deal_id.exists' => 'The selected deal ID does not exist.',
            'booking_id.required' => 'Booking ID is required.',
            'booking_id.exists' => 'The selected booking ID does not exist.',
        ];
    }
}
