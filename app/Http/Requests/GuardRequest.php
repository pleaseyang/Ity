<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardRequest extends FormRequest
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
        return [
            'guard_name' => ['required', 'string', 'between:2,60', Rule::in(['api', 'admin']),],
            'guard_id' => ['required', 'integer', 'exists:' . $this->guardName() . ',id'],
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
    public function messages(): array
    {
        return [

        ];
    }

    /**
     * 获取 gurad Class
     *
     * @return string
     */
    public function guardName(): string
    {
        $guardName = $this->post('guard_name', '');
        return match ($guardName) {
            'api' => 'App\Models\User',
            'admin' => 'App\Models\Admin',
            default => '',
        };
    }
}
