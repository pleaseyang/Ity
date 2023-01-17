<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeChatRequest extends FormRequest
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
            'open' => ['required', 'boolean'],
            'offiaccount_appid' => ['required', 'string'],
            'offiaccount_appsecret' => ['required', 'string'],
            'offiaccount_redirect_uri' => ['required', 'string'],
            'oplatform_appid' => ['required', 'string'],
            'oplatform_appsecret' => ['required', 'string'],
            'oplatform_redirect_uri' => ['required', 'string'],
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
            'open' => __('message.config.wechat.open'),
            'offiaccount_appid' => __('message.config.wechat.offiaccount_appid'),
            'offiaccount_appsecret' => __('message.config.wechat.offiaccount_appsecret'),
            'offiaccount_redirect_uri' => __('message.config.wechat.offiaccount_redirect_uri'),
            'oplatform_appid' => __('message.config.wechat.oplatform_appid'),
            'oplatform_appsecret' => __('message.config.wechat.oplatform_appsecret'),
            'oplatform_redirect_uri' => __('message.config.wechat.oplatform_redirect_uri'),
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
