<?php

namespace App\Http\Requests\Admin\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
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
        $tableNames = config('permission.table_names');
        return [
            'pid' => ['nullable', 'integer', 'exists:' . $tableNames['permissions'] . ',id',],
            'name' => ['required', 'string', 'between:2,60', 'unique:' . $tableNames['permissions'],],
            'title' => ['required', 'string', 'between:2,60',],
            'icon' => ['required', 'string', 'between:2,60',],
            'path' => ['required', 'string', 'between:2,60', 'unique:' . $tableNames['permissions'],],
            'component' => ['required', 'string', 'between:2,60',],
            'guard_name' => ['required', 'string', 'between:2,60', Rule::in(['api', 'admin']),],
            'sort' => ['nullable', 'numeric', 'between:1,999',],
            'hidden' => ['nullable', 'numeric', 'between:0,1',],
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
            'pid' => __('message.permission.pid'),
            'name' => __('message.permission.name'),
            'title' => __('message.permission.title'),
            'icon' => __('message.permission.icon'),
            'path' => __('message.permission.path'),
            'component' => __('message.permission.component'),
            'guard_name' => __('message.permission.guard_name'),
            'sort' => __('message.permission.sort'),
            'hidden' => __('message.permission.hidden'),
        ];
    }

    /**
     * 获取已定义验证规则的错误消息。
     *
     * @return array
     */
    public function messages()
    {
        return [

        ];
    }
}
