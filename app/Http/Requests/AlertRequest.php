<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlertRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target' => ['required', 'in:all,verified,specific'],
            'dispatch_datetime' => 'required|date|after_or_equal:now',
            'channels' => 'required|array|min:1',
            'channels.*' => ['in:email,push'],
            'users' => ['required_if:target,specific', 'array', 'min:1'],
            'users.*' => [Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }
}
