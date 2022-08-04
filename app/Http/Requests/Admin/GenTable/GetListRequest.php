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
            'name' => __('message.gen.name'),
            'comment' => __('message.gen.comment'),
            'engine' => __('message.gen.engine'),
            'charset' => __('message.gen.charset'),
            'collation' => __('message.gen.collation'),
            'created_at_start' => __('message.gen.created_at_start'),
            'created_at_end' => __('message.gen.created_at_end'),
            'updated_at_start' => __('message.gen.updated_at_start'),
            'updated_at_end' => __('message.gen.updated_at_end'),
        ]);
    }
}
