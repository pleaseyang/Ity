<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDingTalkRequest extends FormRequest
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
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'corp_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'redirect_bind_uri' => ['required', 'string'],
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
            'open' => __('message.config.dingTalk.open'),
            'client_id' => __('message.config.dingTalk.client_id'),
            'client_secret' => __('message.config.dingTalk.client_secret'),
            'corp_id' => __('message.config.dingTalk.corp_id'),
            'redirect_uri' => __('message.config.dingTalk.redirect_uri'),
            'redirect_bind_uri' => __('message.config.dingTalk.redirect_bind_uri'),
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
