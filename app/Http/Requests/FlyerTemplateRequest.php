<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlyerTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:daily_gold_rate,daily_wishes,birthday_card,work_anniversary,product_rate,custom'],
            'paper_size' => ['required', 'in:a4,custom'],
            'canvas_width' => ['required', 'integer', 'min:400', 'max:3000'],
            'canvas_height' => ['required', 'integer', 'min:400', 'max:3000'],
            'background_type' => ['required', 'in:color,image'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'background_image' => ['nullable', 'image', 'max:4096'],
            'remove_background_image' => ['nullable', 'boolean'],
            'elements' => ['required', 'array', 'min:1'],
            'elements.*.id' => ['required', 'string', 'max:100'],
            'elements.*.type' => ['required', 'in:text,image'],
            'elements.*.key' => ['nullable', 'string', 'max:100'],
            'elements.*.label' => ['required', 'string', 'max:255'],
            'elements.*.content' => ['nullable', 'string', 'max:2000'],
            'elements.*.placeholder' => ['nullable', 'string', 'max:100'],
            'elements.*.x' => ['required', 'integer', 'min:0', 'max:4000'],
            'elements.*.y' => ['required', 'integer', 'min:0', 'max:4000'],
            'elements.*.width' => ['nullable', 'integer', 'min:0', 'max:4000'],
            'elements.*.height' => ['nullable', 'integer', 'min:0', 'max:4000'],
            'elements.*.font_size' => ['nullable', 'integer', 'min:8', 'max:200'],
            'elements.*.color' => ['nullable', 'string', 'max:20'],
            'elements.*.alignment' => ['nullable', 'in:left,center,right'],
            'elements.*.font_weight' => ['nullable', 'in:normal,medium,semibold,bold'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
