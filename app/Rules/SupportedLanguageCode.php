<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Nbsbbs\Common\Language\LanguageFactory;

class SupportedLanguageCode implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return LanguageFactory::isValidCode($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be valid language code';
    }
}
