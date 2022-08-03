<?php

namespace App\Http\Requests\Admin\GenTable;

use App\Http\Requests\GetListRequest as CommonRequest;
use Illuminate\Foundation\Http\FormRequest;

class GetListRequest extends FormRequest
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
        return array_merge((new CommonRequest())->rules(), [
            'name' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'engine' => ['nullable', 'string'],
            'charset' => ['nullable', 'string'],
            'collation' => ['nullable', 'string'],
            'created_at_start' => ['nullable', 'date'],
            'created_at_end' => ['nullable', 'date'],
            'updated_at_start' => ['nullable', 'date'],
            'updated_at_end' => ['nullable', 'date'],
        ]);
    }

    /**
     * 获取已定义验证规则的错误消息。
     *
     * @return array
     */
    public function attributes(): array
    {
        return array_merge((new CommonRequest())->attributes(), [
            'name' => '表名称',
            'comment' => '表描述',
            'engine' => '表引擎',
            'charset' => '字符集',
            'collation' => '排序规则',
            'created_at_start' => '创建时间开始',
            'created_at_end' => '创建时间结束',
            'updated_at_start' => '更新时间开始',
            'updated_at_end' => '更新时间结束',
        ]);
    }
}
