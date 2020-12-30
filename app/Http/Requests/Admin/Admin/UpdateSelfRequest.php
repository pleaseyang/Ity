<?php

namespace App\Http\Requests\Admin\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSelfRequest extends FormRequest
{
    public $admin;

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
        return [
            'name' => [
                'required', 'string', Rule::unique('admins')->ignore($this->user('admin')), 'between:2,60'
            ],
            'email' => [
                'required', 'string', 'email', Rule::unique('admins')->ignore($this->user('admin')), 'between:2,60'
            ],
            'password' => ['nullable', 'string', 'between:6,60'],
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
            'id' => __('message.admin.id'),
            'name' => __('message.admin.name'),
            'email' => __('validation.attributes.email'),
            'password' => __('validation.attributes.password'),
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
