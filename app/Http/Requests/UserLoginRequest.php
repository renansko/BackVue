<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserLoginRequest extends FormRequest
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
        return [
            'email' => 'required|max:255|email',
            'password' => 'required|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => "O email deve ser informado",
            'email.max' => "O email deve conter no máximo 255 caracteres",
            'email.email' => "O email deve ser válido. Exemplo: exemplo@exemplo.com.br",
            'password.required' => "A password deve ser informada",
            'password.max' => "A password deve conter no máximo 255 caracteres"

        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}