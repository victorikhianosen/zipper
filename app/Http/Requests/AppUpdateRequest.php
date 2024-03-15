<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppUpdateRequest extends FormRequest
{
    protected string $type;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $this->type = $this->resolveActionName() == 'create' ? 'create' : 'edit';

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->type;
        $data = [
            'version' => 'bail|required',
            'build_number' => 'bail|required',
            'store_link' => 'bail|url|required',
            'is_required' => 'bail|boolean|required',
            'platform' => 'bail|required|'.Rule::in(['android', 'ios']),
        ];
        if ($type == 'edit') {
            $data['id'] = 'bail|required|exists:app_updates,id';
        }

        return $data;
    }
}
