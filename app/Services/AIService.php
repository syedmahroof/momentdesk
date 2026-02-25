<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerDate;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Prism;

class AIService
{
    public function generateWish(Customer $customer, CustomerDate $date, string $tone = 'friendly'): string
    {
        $response = Prism::text()
            ->using(Provider::OpenAI, config('prism.providers.openai.model', 'gpt-4o-mini'))
            ->withSystemPrompt('You are an expert at writing warm, personalized greeting messages. Keep messages concise, heartfelt, and suitable for sending directly.')
            ->withPrompt($this->buildPrompt($customer, $date, $tone))
            ->generate();

        return trim($response->text);
    }

    public function improveTemplate(string $content, string $tone = 'friendly'): string
    {
        $response = Prism::text()
            ->using(Provider::OpenAI, config('prism.providers.openai.model', 'gpt-4o-mini'))
            ->withSystemPrompt('You are an expert at writing warm, personalized greeting messages. Preserve any {{variable}} placeholders exactly as they are.')
            ->withPrompt("Rewrite this message in a {$tone} tone, keeping it warm and personal. Do NOT change any {{variable}} placeholders:\n\n{$content}")
            ->generate();

        return trim($response->text);
    }

    private function buildPrompt(Customer $customer, CustomerDate $date, string $tone): string
    {
        $name = $customer->name;

        return match ($date->type) {
            'birthday' => "Write a {$tone} birthday wish for {$name}. Make it warm, personal, and celebratory. Keep it under 3 sentences.",
            'wedding' => "Write a {$tone} {$date->ordinal_years} wedding anniversary wish for {$name}. Celebrate their love and commitment. Keep it under 3 sentences.",
            'work' => "Write a {$tone} {$date->ordinal_years} work anniversary message for {$name}. Recognize their dedication and achievements. Keep it under 3 sentences.",
            default => "Write a {$tone} greeting for {$name} for their {$date->display_title}. Make it warm and personal. Keep it under 3 sentences.",
        };
    }
}
