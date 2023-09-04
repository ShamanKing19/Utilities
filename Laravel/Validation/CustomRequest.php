<?php
namespace App\Http\Requests;

class ProjectRequest extends \Illuminate\Foundation\Http\FormRequest
{
    /**
     * Проверка доступа пользователя к методу
     */
    public function authorize(\Illuminate\Http\Request $request) : bool
    {
        $user = $request->user();
        $projectId = $request->post('project_id') ?? $request->post('id');
        if($projectId) {
            return $user->hasProject($projectId);
        }

        return !empty($user);
    }

    /**
     * Правила валидации
     */
    public function rules() : array
    {
        return [
            'name' => ['required', 'max:255'],
            'code' => ['max:255'],
            'image' => ['image'],
            // ! Правило 'befor:active_to' не будет проверено, если не передан параметр 'active_to', благодаря правилу 'exclude_without:active_to'
            'active_from' => ['required', 'date', 'exclude_without:active_to', 'before:active_to'],
            'active_to' => ['date', 'exclude_without:active_from', 'after:active_from'],
            'service' => ['max:255'],
            'crm_id' => ['integer', 'unique:projects'],
            'company_id' => ['integer', 'exists:\App\Models\Company,id'],
            'payment_type_id' => ['integer', 'exists:\App\Models\PaymentType,id']
        ];
    }

    /**
     * Вывод ошибок валидации
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException([
            'status' => false,
            'message' => __('response.validation_error'),
            'data' => $validator->errors()
        ]);
    }
}
