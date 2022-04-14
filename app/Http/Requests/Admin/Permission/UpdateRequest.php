<?php

namespace App\Http\Requests\Admin\Permission;

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
        $tableNames = config('permission.table_names');
        return [
            'id' => ['required', 'integer',],
            'pid' => [
                'nullable', 'integer', 'different:id',
                'exists:' . $tableNames['permissions'] . ',id',
            ],
            'name' => [
                'required', 'string', 'between:2,60',
                Rule::unique($tableNames['permissions'])->ignore($id),
            ],
            'title' => ['required', 'string', 'between:2,60',],
            'icon' => ['required', 'string', 'between:2,60',],
            'path' => [
                'required', 'string', 'between:2,60',
                Rule::unique($tableNames['permissions'])->ignore($id),
            ],
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
    public function attributes(): array
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
    public function messages(): array
    {
        return [

        ];
    }
}
