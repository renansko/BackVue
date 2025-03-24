<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanyRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $update = $this->isMethod('PUT');
        return [
                'name' => !$update ? 'required|max:255' : 'sometimes|max:255',
                'email' => !$update ? 'required|email' : 'sometimes|email',
            ];
      
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Name must have a maximum of 255 characters',
            
            'email.required'    => 'Email is required',
            'email.email'       => 'Please enter a valid email',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}