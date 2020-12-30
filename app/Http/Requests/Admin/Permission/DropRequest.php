<?php

namespace App\Http\Requests\Admin\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DropRequest extends FormRequest
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
            'dragging' => ['required', 'integer', 'exists:' . $tableNames['permissions'] . ',id',],
            'drop' => ['required', 'integer', 'different:dragging', 'exists:' . $tableNames['permissions'] . ',id',],
            'type' => ['required', 'string', Rule::in(['before', 'after', 'inner']),],
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
            'dragging' => __('message.permission.permission'),
            'drop' => __('message.permission.permission'),
            'type' => __('message.permission.type'),
        ];
    }
}
