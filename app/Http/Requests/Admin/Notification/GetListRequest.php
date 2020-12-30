<?php

namespace App\Http\Requests\Admin\Notification;

use App\Http\Requests\GetListRequest as CommonRequest;
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
        return array_merge((new CommonRequest())->rules(), [
            'message' => ['nullable', 'string',],
            'is_read' => ['nullable', 'integer', Rule::in(0, 1)],
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
            'message' => __('message.notification.message'),
            'is_read' => __('message.notification.is_read'),
        ]);
    }
}
