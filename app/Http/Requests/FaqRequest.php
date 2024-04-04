<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class FaqRequest extends FormRequest
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
        $data = [
            'question' => 'bail|required',
            'answer' => 'bail|required',
            'faq_category_id' => 'bail|required|string|exists:faq_categories,id',
        ];

        return $data;
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key);
        $type = $this->resolveActionName();
        if ($type == 'createFaq') {
            $data['slug'] = Str::slug($data['question']).'-'.time();
        }

        return $data;
    }
}
