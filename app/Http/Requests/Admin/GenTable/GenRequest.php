<?php

namespace App\Http\Requests\Admin\GenTable;

use Illuminate\Foundation\Http\FormRequest;

class GenRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'comment' => ['required', 'string'],
            'pid' => ['nullable', 'array'],
        ];
    }

    /**
     * 获取已定义验证规则的错误消息。
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => '表名称',
            'comment' => '表描述',
            'pid' => '所属分类',
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
