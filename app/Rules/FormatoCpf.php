<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * @author Wallace Maxters <wallacemaxters@gmail.com>
 */
class FormatoCpf implements Rule
{
    /**
     * Valida o formato do cpf
     *
     * @param string $attribute
     * @param string $value
     * @return boolean
    */
    public function passes($attribute, $value)
    {

        return preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $value) === 0;
    }

    public function message()
    {
        return 'O docuemnto nao pode ter caracteres especias.';
    }
}