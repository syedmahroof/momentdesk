<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'dates' => ['nullable', 'array'],
            'dates.*.type' => ['required', 'in:birthday,wedding,work,custom'],
            'dates.*.title' => ['nullable', 'string', 'max:255'],
            'dates.*.date' => ['required', 'date'],
            'dates.*.reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:30'],
            'dates.*.active' => ['nullable', 'boolean'],
            'dates.*.auto_send' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'dates.*.date.required' => 'Each event must have a date.',
            'dates.*.type.in' => 'Event type must be birthday, wedding, work, or custom.',
        ];
    }
}
