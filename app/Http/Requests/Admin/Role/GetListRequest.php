<?php

namespace App\Http\Requests\Admin\Role;

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
            'guard_name' => ['required', 'string', Rule::in(['api', 'admin']),],
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
            'name' => __('message.role.name'),
            'guard_name' => __('message.permission.guard_name'),
        ]);
    }
}
