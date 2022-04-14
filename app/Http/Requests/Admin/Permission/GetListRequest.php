<?php

namespace App\Http\Requests\Admin\Permission;

use App\Http\Requests\GetListRequest as CommonRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'name' => ['nullable', 'string',],
            'title' => ['nullable', 'string',],
            'path' => ['nullable', 'string',],
            'guard_name' => ['required', 'string', Rule::in(['api', 'admin']),],
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
            'name' => __('message.permission.name'),
            'title' => __('message.permission.title'),
            'path' => __('message.permission.path'),
            'guard_name' => __('message.permission.guard_name'),
        ]);
    }
}
