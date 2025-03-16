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
            'email' => !$update ? 'required|email,' : 'sometimes|email',
            'phone' => !$update ? 'required|string' : 'sometimes|string',
            'company_id' => !$update ? 'required|uuid|exists:companies,id' : 'sometimes|uuid|exists:companies,id',
            'password' => !$update ? [
                'required',
                'confirmed', // Confirmação de senha
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                ->uncompromised()
                    ->rules([
                        'max:255',
                        function($attributes, $value, $fail){
                            $invalidPatterns = [
                                $this->input('email'),
                                $this->input('name'),
                                $this->input('username'),
                                '123', 'abc', 'senha', 'password'
                            ];

                            foreach($invalidPatterns as $patern){
                                if($patern && stripos($value, $patern) !== false){
                                    $fail("A senha contém informações inseguras.");
                                }
                            }
                        }
                    ]),
                // 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{12,}$/'
            ]: 'prohibited',
            'password_confirmation' => !$update ? 'required' : 'prohibited',
            // 'documento' => !$update ? [
            // 'required',
            // 'string',
            // 'cpf_ou_cnpj',
            // 'cpf_formater_to_db',
            // ] : [
            //     'sometimes',    
            // 'string',
            // 'cpf_ou_cnpj',
            // 'cpf_formater_to_db',
            // ],
          
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

            'phone.required' => 'Phone is required',
            'phone.string' => 'Phone must be text',
            'phone.regex' => 'Phone must be in the format (XX) XXXXX-XXXX or (XX) XXXX-XXXX',
            
            // 'documento.required'    => 'Document is required',
            // 'documento.string'      => 'Document must be text',
            // 'documento.cpf_ou_cnpj' => 'Please enter a valid CPF or CNPJ',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}