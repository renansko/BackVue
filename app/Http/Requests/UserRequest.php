<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
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
            'phones' => 'sometimes|array',
            'phones.*' => 'string|min:4|max:15',
            'company_id' =>'sometimes|uuid|exists:companies,id',
            'password' => !$update ? [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ] : 'prohibited',
            'password_confirmation' => !$update ? 'required' : 'prohibited',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Name must have a maximum of 255 characters',
            
            'email.required'    => 'Email is required',
            'email.email'       => 'Please enter a valid email',
            
            'password.required'         => 'Password is required',
            'password.confirmed'        => 'Passwords do not match, make sure you pass the ´password_confirmation´ fild',
            'password.min'              => 'Password must be at least 8 characters',
            'password.max'              => 'Password must have a maximum of 255 characters',
            'password.mixed_case'       => 'Password must contain uppercase and lowercase letters',
            'password.numbers'          => 'Password must contain numbers',
            'password.symbols'          => 'Password must contain symbols',
            'password.uncompromised'    => 'This password appeared in a data breach. Please choose a different password',
            
            'password_confirmation.required' => 'Password confirmation is required',
            'phones.required' => 'At least one phone is required',
            'phones.array' => 'Phones must be provided as a list',
            'phones.*.string' => 'Phone numbers must be text',
            'phones.*.min' => 'Phone numbers must be at least 4 characters',
            'phones.*.max' => 'Phone numbers cannot exceed 15 characters',
            
            'company_id.uuid' => 'Company ID must be a valid UUID',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}