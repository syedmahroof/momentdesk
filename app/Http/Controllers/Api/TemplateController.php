<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = Template::query()
            ->when($request->string('type')->toString(), fn ($query, string $type) => $query->where('type', $type))
            ->latest()
            ->get()
            ->map(fn (Template $template) => $this->serialize($template));

        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $template = Template::create($this->validated($request));

        return response()->json($this->serialize($template), 201);
    }

    public function update(Request $request, Template $template): JsonResponse
    {
        $template->update($this->validated($request));

        return response()->json($this->serialize($template));
    }

    public function destroy(Template $template): JsonResponse
    {
        $template->delete();

        return response()->json(['message' => 'Template deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:birthday,wedding,work,custom'],
            'channel' => ['required', 'in:whatsapp,email,sms'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Template $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'type' => $template->type,
            'channel' => $template->channel,
            'subject' => $template->subject,
            'content' => $template->content,
            'is_default' => $template->is_default,
        ];
    }
}
