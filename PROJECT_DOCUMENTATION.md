# MomentDesk Project Documentation

## Overview

MomentDesk is a Laravel 12 + Inertia Vue application for managing customers, important dates, templates, flyers, and automated wish delivery. It supports multi-tenancy, social login, email verification, two-factor authentication, and AI-assisted content generation.

## Tech Stack

- Backend: `Laravel 12`, `PHP`, `Fortify`, `Socialite`, `Wayfinder`
- Frontend: `Inertia.js v2`, `Vue 3`, `TypeScript`, `Tailwind CSS v4`, `Vite`
- AI: `prism-php/prism`, `openai-php/laravel`
- Messaging: WhatsApp Cloud API, Laravel Mail
- Testing: `Pest v4` + Laravel test tooling

## Repository Structure

- `app/` - Controllers, models, services, actions, middleware, console commands
- `bootstrap/` - Laravel 12 application bootstrap and middleware/scheduling setup
- `config/` - Service configuration (Fortify, Socialite providers, app settings)
- `database/` - Migrations, factories, and seeders
- `resources/js/` - Inertia Vue app pages, components, layouts, routes
- `routes/` - HTTP route definitions (`web.php`, `settings.php`)
- `tests/` - Pest feature tests and supporting test setup
- `public/`, `storage/` - Public/runtime files

## Local Setup

### Prerequisites

- PHP and Composer
- Node.js and npm
- Database configured in `.env`

### Install and Bootstrap

```bash
composer setup
```

This runs:

- `composer install`
- `.env` creation from `.env.example` (if missing)
- app key generation
- migrations
- npm install
- frontend build

### Development Commands

Run full local development stack (server, queue, logs, Vite):

```bash
composer dev
```

Run SSR development stack:

```bash
composer dev:ssr
```

Run frontend only:

```bash
npm run dev
```

Build frontend assets:

```bash
npm run build
```

## Backend Architecture

### Routing

Main route files:

- `routes/web.php`
- `routes/settings.php`

Key route groups:

- Public: home + social auth redirect/callback
- Authenticated tenant users (`auth`, `verified`, `ensure-tenant`):
  - dashboard
  - customers (resource routes)
  - templates (resource routes)
  - flyer templates (resource routes)
  - flyers (selected resource routes)
  - wishes send/bulk send
  - AI endpoints
- Admin-only (`can:super-admin`): tenant management resource routes

### Controllers

Primary domain controllers in `app/Http/Controllers`:

- `CustomerController`
- `WishController`
- `TemplateController`
- `FlyerController`
- `FlyerTemplateController`
- `DashboardController`
- `AIController`

Auth/settings/admin controllers are organized under:

- `app/Http/Controllers/Auth`
- `app/Http/Controllers/Settings`
- `app/Http/Controllers/Admin`

### Services

Core service classes:

- `app/Services/WishService.php` - Orchestrates wish sending and channel dispatch
- `app/Services/AIService.php` - Handles AI text generation and template improvement

### Multi-Tenancy

Tenant isolation is implemented through model-level conventions and middleware:

- Trait: `app/Traits/HasTenant.php`
- Scope: `app/Scopes/TenantScope.php`
- Middleware alias usage: `ensure-tenant`

### Scheduling

Laravel 12 scheduling is configured in `bootstrap/app.php`.

Scheduled command:

- `app/Console/Commands/SendReminders.php` (`momentdesk:send-reminders`)

## Authentication and Security

### Fortify

Fortify setup:

- `config/fortify.php`
- `app/Providers/FortifyServiceProvider.php`

Configured capabilities include:

- login/logout
- registration
- password reset
- email verification
- two-factor authentication

Fortify actions:

- `app/Actions/Fortify/CreateNewUser.php`
- `app/Actions/Fortify/ResetUserPassword.php`
- additional profile/password update actions in the same directory

### Social Login

Social authentication controller:

- `app/Http/Controllers/Auth/SocialAuthController.php`

Provider credentials and callback config:

- `config/services.php`

Current provider flow is exposed via:

- `auth/{provider}/redirect`
- `auth/{provider}/callback`

### Settings Area

User and tenant settings routes are defined in `routes/settings.php` and include:

- profile
- password update (throttled)
- appearance
- two-factor settings
- tenant profile

## Frontend Architecture (Inertia + Vue)

### App Entry

- `resources/js/app.ts` - Inertia client bootstrap
- `resources/js/ssr.ts` - SSR bootstrap

### Pages

Pages are in `resources/js/pages`, including:

- `Dashboard.vue`
- `Welcome.vue`
- `auth/*`
- `settings/*`
- `Customers/*`
- `Templates/*`
- `Flyers/*`
- `FlyerTemplates/*`
- `Wishes/Send.vue`
- `Admin/Tenants/*`

### Layouts and Components

- Layouts: `resources/js/layouts/*`
- Shared UI and domain components: `resources/js/components/*`

### Wayfinder Typed Routes

Wayfinder is enabled for typed route generation:

- Plugin setup: `vite.config.ts`
- Generated route helpers: `resources/js/routes/*`

Use generated helpers instead of hardcoded URLs in Vue pages/components.

## AI Features

AI endpoints are provided in `routes/web.php` under the `ai.` route name prefix:

- `POST /ai/generate-wish`
- `POST /ai/improve-template`

Controller:

- `app/Http/Controllers/AIController.php`

Behavior:

- `generateWish` validates customer/date context and returns generated wish text
- `improveTemplate` validates template content and returns improved content

## Testing

Testing stack:

- Pest v4 + Laravel testing tools

Core files:

- `tests/Pest.php`
- `phpunit.xml`

Run all tests:

```bash
php artisan test --compact
```

Run a specific file:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php
```

Notable feature test areas:

- authentication and registration
- two-factor authentication and challenge flows
- social authentication
- dashboard and domain workflows
- flyer and flyer template operations

## Linting and Formatting

### PHP

```bash
vendor/bin/pint --format agent
```

### Frontend

```bash
npm run lint
npm run format
```

## Operational Notes

- Queue worker is part of `composer dev`; background jobs are expected for send flows.
- If frontend changes are not visible, run `npm run dev` or rebuild with `npm run build`.
- Ensure social provider credentials and AI keys are configured in `.env` and corresponding config files.

## Quick Feature Map

- Customer management: CRUD via `CustomerController`
- Template management: CRUD via `TemplateController`
- Flyer workflow: create/list/show/delete via `FlyerController`
- Flyer template management: CRUD via `FlyerTemplateController`
- Wish sending: individual and bulk sends via `WishController`
- AI assistant: wish generation and template improvement via `AIController`
- Tenant administration: admin-only tenant CRUD via `Admin/TenantController`

