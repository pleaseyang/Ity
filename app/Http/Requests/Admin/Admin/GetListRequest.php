<?php

namespace App\Http\Requests\Admin\Admin;

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
        return array_merge((new CommonRequest())->rules(), [
            'name' => ['nullable', 'string', 'between:1,60',],
            'email' => ['nullable', 'string', 'between:1,60',],
            'status' => ['nullable', 'integer', Rule::in(0, 1)],
            'role_ids' => ['nullable', 'array',],
        ]);
    }

    /**
     * 获取已定义验证规则的错误消息。
     *
     * @return array
     */
    public function attributes()
    {
        return array_merge((new CommonRequest())->attributes(), [
            'name' => __('message.admin.name'),
            'email' => __('validation.attributes.email'),
            'status' => __('message.admin.status'),
            'role_ids' => __('message.role.id'),
        ]);
    }
}
