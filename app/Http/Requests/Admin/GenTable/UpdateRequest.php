<?php

namespace App\Http\Requests\Admin\GenTable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
        $id = $this->post('id', 0);
        return [
            'id' => ['required', 'integer', Rule::exists('gen_tables', 'id')],
            'name' => ['required', 'string'],
            'comment' => ['required', 'string'],
            'engine' => ['required', 'string'],
            'charset' => ['required', 'string'],
            'collation' => ['required', 'string'],
            'gen_table_columns' => ['required', 'array'],
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
            'id' => '自增ID',
            'name' => '表名称',
            'comment' => '表描述',
            'engine' => '表引擎',
            'charset' => '字符集',
            'collation' => '排序规则',
            'gen_table_columns' => '配置项',
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
