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
            'name' => __('message.gen.name'),
            'comment' => __('message.gen.comment'),
            'engine' => __('message.gen.engine'),
            'charset' => __('message.gen.charset'),
            'collation' => __('message.gen.collation'),
            'gen_table_columns' => __('message.gen.gen_table_columns'),
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
