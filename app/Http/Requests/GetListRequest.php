<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'order' => ['required', 'string', Rule::in(['descending', 'ascending'])],
            'sort' => ['nullable', 'string'],
            'start_at' => ['required_with:end_at', 'date_format:Y-m-d H:i:s', 'before_or_equal:end_at'],
            'end_at' => ['required_with:start_at', 'date_format:Y-m-d H:i:s', 'after_or_equal:start_at'],
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
            'order' => __('message.common.order'),
            'sort' => __('message.common.sort'),
            'start_at' => __('message.common.start_at'),
            'end_at' => __('message.common.end_at'),
        ];
    }
}
