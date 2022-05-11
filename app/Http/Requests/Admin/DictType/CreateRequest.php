<?php

namespace App\Http\Requests\Admin\DictType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'unique:dict_types', 'max:100'],
            'status' => ['nullable', 'integer', Rule::in([0, 1])],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * 获取验证错误的自定义属性。
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => __('message.dict_type.name'),
            'type' => __('message.dict_type.type'),
            'status' => __('message.dict_type.status'),
            'remark' => __('message.dict_type.remark'),
        ];
    }

    /**
     * 获取已定义验证规则的错误消息。
     *
     * @return array
     */
    public function messages(): array
    {
        return [

        ];
    }
}
