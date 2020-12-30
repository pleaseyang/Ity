<?php

namespace App\Http\Requests\Admin\Role;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->guardName() !== '') {
            return  true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'guard_name' => ['required', 'string', 'between:2,60', Rule::in(['api', 'admin']),],
            'guard_id' => ['required', 'integer', 'exists:' . $this->guardName() . ',id'],
            'roles' => ['array']
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
            'roles' => __('message.role.id'),
            'guard_name' => __('message.permission.guard_name'),
            'guard_id' => __('message.role.guard_id'),
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

    /**
     * 获取 gurad Name
     *
     * @return string
     */
    public function guardName()
    {
        $guardName = $this->post('guard_name', '');
        switch ($guardName) {
            case 'api':
                return 'App\Models\User';
            case 'admin':
                return 'App\Models\Admin';
            default:
                return '';
        }
    }


    /**
     * 获取 gurad Name
     *
     * @return Admin|User|null
     */
    public function guard()
    {
        $guardName = $this->post('guard_name', '');
        $guardId = $this->post('guard_id', '');
        switch ($guardName) {
            case 'api':
                return User::find($guardId);
            case 'admin':
                return Admin::find($guardId);
            default:
                return null;
        }
    }
}
