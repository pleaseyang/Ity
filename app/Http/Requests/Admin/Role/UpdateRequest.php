<?php

namespace App\Http\Requests\Admin\Role;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class UpdateRequest extends FormRequest
{
    public $role;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        try {
            $this->role = Role::find($this->post('id', 0));
        } catch (RoleDoesNotExist $exception) {
            return false;
        }
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
            'id' => ['required', 'integer',],
            'name' => ['required', 'string', 'between:2,60', Rule::unique($tableNames['roles'])->ignore($this->role),],
            'guard_name' => ['required', 'string', 'between:2,60', Rule::in(['api', 'admin']),],
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
            'name' => __('message.role.name'),
            'guard_name' => __('message.permission.guard_name'),
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
