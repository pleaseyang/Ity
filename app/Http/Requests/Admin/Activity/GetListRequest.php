<?php

namespace App\Http\Requests\Admin\Activity;

use App\Http\Requests\GetListRequest as CommonRequest;
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
        return array_merge((new CommonRequest())->rules(), [
            'log_name' => ['nullable', 'string',],
            'description' => ['nullable', 'string',],
            'subject_id' => ['nullable', 'string',],
            'subject_type' => ['nullable', 'string',],
            'causer_id' => ['nullable', 'string',],
            'causer_type' => ['nullable', 'string',],
            'properties' => ['nullable', 'string',],
        ]);
    }

    /**
     * 获取已定义验证规则的错误消息。
     *
     * @return array
     */
    public function attributes()
    {
        return array_merge((new CommonRequest())->attributes(), [
            'log_name' => __('message.activity.log_name'),
            'description' => __('message.activity.description'),
            'subject_id' => __('message.activity.subject_id'),
            'subject_type' => __('message.activity.subject_type'),
            'causer_id' => __('message.activity.causer_id'),
            'causer_type' => __('message.activity.causer_type'),
            'properties' => __('message.activity.properties'),
        ]);
    }
}
