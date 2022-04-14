<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

/**
 * 不能以 指定字符串 开头
 *
 * Class UnStartsWith
 * @package App\Rules
 */
class UnStartsWith implements Rule
{

    /**
     * @var string
     */
    private string $str;

    /**
     * Create a new rule instance.
     *
     * @param string $str
     */
    public function __construct(string $str = '/')
    {
        $this->str = $str;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return !Str::startsWith($value, $this->str);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.un_starts_with', ['values' => $this->str]);
    }
}
