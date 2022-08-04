<?php

namespace App\Http\Requests\Admin\DictData;

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
        return array_merge((new CommonRequest())->rules(), [
            'dict_type_id' => ['required', 'integer', Rule::exists('dict_types', 'id')],
            'label' => ['nullable', 'string', 'max:100'],
            'value' => ['nullable', 'string', 'max:100'],
            'default' => ['nullable', 'integer'],
            'status' => ['nullable', 'integer'],
        ]);
    }

    /**
     * 获取已定义验证规则的错误消息。
     *
     * @return array
     */
    public function attributes(): array
    {
        return array_merge((new CommonRequest())->attributes(), [
            'dict_type_id' => __('message.dict_data.dict_type_id'),
            'label' => __('message.dict_data.label'),
            'value' => __('message.dict_data.value'),
            'default' => __('message.dict_data.default'),
            'status' => __('message.dict_data.status'),
        ]);
    }
}
