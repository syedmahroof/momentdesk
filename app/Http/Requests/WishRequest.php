<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'customer_date_id' => ['required', 'exists:customer_dates,id'],
            'channel' => ['required', 'in:whatsapp,email,sms'],
            'template_id' => ['nullable', 'exists:templates,id'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
