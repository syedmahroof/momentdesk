<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateRequest;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(): Response
    {
        $templates = Template::query()->latest()->get();

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Templates/Create');
    }

    public function store(TemplateRequest $request): RedirectResponse
    {
        Template::create($request->validated());

        return redirect()->route('templates.index')->with('success', 'Template created.');
    }

    public function edit(Template $template): Response
    {
        return Inertia::render('Templates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(TemplateRequest $request, Template $template): RedirectResponse
    {
        $template->update($request->validated());

        return redirect()->route('templates.index')->with('success', 'Template updated.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted.');
    }
}
