<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'flyer_template_id' => ['required', 'integer'],
            'paper_size' => ['required', 'in:a4,custom'],
            'canvas_width' => ['required', 'integer', 'min:400', 'max:3000'],
            'canvas_height' => ['required', 'integer', 'min:400', 'max:3000'],
            'field_values' => ['nullable', 'array'],
            'field_values.date' => ['nullable', 'string', 'max:255'],
            'field_values.title' => ['nullable', 'string', 'max:255'],
            'field_values.price' => ['nullable', 'string', 'max:255'],
            'field_values.message' => ['nullable', 'string', 'max:5000'],
            'field_values.product_name' => ['nullable', 'string', 'max:255'],
            'element_overrides' => ['nullable', 'array'],
            'element_overrides.*.x' => ['nullable', 'integer', 'min:0', 'max:4000'],
            'element_overrides.*.y' => ['nullable', 'integer', 'min:0', 'max:4000'],
            'element_overrides.*.font_size' => ['nullable', 'integer', 'min:8', 'max:200'],
            'element_overrides.*.color' => ['nullable', 'string', 'max:20'],
            'element_overrides.*.alignment' => ['nullable', 'in:left,center,right'],
            'element_overrides.*.font_weight' => ['nullable', 'in:normal,medium,semibold,bold'],
            'logo_image' => ['nullable', 'image', 'max:4096'],
            'featured_image' => ['nullable', 'image', 'max:4096'],
            'background_image' => ['nullable', 'image', 'max:8192'],
        ];
    }
}
