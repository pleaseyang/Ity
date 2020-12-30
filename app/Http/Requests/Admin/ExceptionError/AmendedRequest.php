<?php

namespace App\Http\Requests\Admin\ExceptionError;

use Illuminate\Foundation\Http\FormRequest;

class AmendedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => ['required', 'string', 'exists:exception_errors',],
            'solve' => ['required', 'integer',]
        ];
    }

    /**
     * 获取验证错误的自定义属性。
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'id' => __('message.exception.id'),
            'solve' => __('message.exception.solve'),
        ];
    }
}
