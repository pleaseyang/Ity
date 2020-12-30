<?php

namespace App\Http\Requests\Admin\Nginx;

use Illuminate\Foundation\Http\FormRequest;

class GetListRequest extends FormRequest
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
        return [
            'offset' => ['required', 'numeric', 'gte:0'],
            'limit' => ['required', 'numeric', 'gte:1', 'lte:50'],
            'start_at' => ['required_with:end_at', 'date_format:Y-m-d H:i:s', 'before_or_equal:end_at'],
            'end_at' => ['required_with:start_at', 'date_format:Y-m-d H:i:s', 'after_or_equal:start_at'],
            'file' => ['required', 'string',],
            'ip' => ['nullable', 'string',],
            'method' => ['nullable', 'string',],
            'uri' => ['nullable', 'string',],
            'http_code' => ['nullable', 'string',],
            'is_warning' => ['nullable', 'boolean',],
            'is_error' => ['nullable', 'boolean',],
            'is_robot' => ['nullable', 'boolean',],
            'is_mobile' => ['nullable', 'boolean',],
        ];
    }

    /**
     * 获取已定义验证规则的错误消息。
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'offset' => __('message.common.offset'),
            'limit' => __('message.common.limit'),
            'start_at' => __('message.common.start_at'),
            'end_at' => __('message.common.end_at'),
            'file' => __('message.nginx.file'),
            'ip' => __('message.nginx.ip'),
            'method' => __('message.nginx.method'),
            'uri' => __('message.nginx.uri'),
            'http_code' => __('message.nginx.http_code'),
            'is_warning' => __('message.nginx.is_warning'),
            'is_error' => __('message.nginx.is_error'),
            'is_robot' => __('message.nginx.is_robot'),
            'is_mobile' => __('message.nginx.is_mobile'),
        ];
    }
}
