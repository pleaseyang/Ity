<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAliOssRequest extends FormRequest
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
            'access_key_id' => ['required', 'string'],
            'access_key_secret' => ['required', 'string'],
            'bucket_name' => ['required', 'string'],
            'endpoint' => ['required', 'string'],
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
            'access_key_id' => __('message.config.aliOss.access_key_id'),
            'access_key_secret' => __('message.config.aliOss.access_key_secret'),
            'bucket_name' => __('message.config.aliOss.bucket_name'),
            'endpoint' => __('message.config.aliOss.endpoint'),
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
