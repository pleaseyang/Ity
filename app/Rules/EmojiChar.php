<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * 检测是否含有 emoji字符
 *
 * Class EmojiChar
 * @package App\Rules
 */
class EmojiChar implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $mbLen = mb_strlen($value);
        $strArr = [];
        $return = false;
        for ($i = 0; $i < $mbLen; $i++) {
            $strArr[] = mb_substr($value, $i, 1, 'utf-8');
            if (strlen($strArr[$i]) >= 4) {
                $return = true;
            }
        }
        return !$return;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.emoji');
    }
}
