<?php

namespace App\Console\Commands;

use App\Models\CustomerDate;
use App\Models\Template;
use App\Services\WishService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendReminders extends Command
{
    protected $signature = 'momentdesk:send-reminders {--dry-run : Preview without sending}';

    protected $description = 'Send automatic birthday and anniversary reminders';

    public function handle(WishService $wishService): int
    {
        $this->info('Checking upcoming events...');

        $dates = CustomerDate::query()
            ->with(['customer', 'tenant'])
            ->where('active', true)
            ->where('auto_send', true)
            ->whereHas('customer', fn (Builder $q) => $q->whereNotNull('tenant_id'))
            ->get()
            ->filter(fn ($date) => $date->days_until <= $date->reminder_days_before);

        $this->info("Found {$dates->count()} event(s) to process.");

        foreach ($dates as $date) {
            $customer = $date->customer;

            // Bypass global scope since this runs without auth
            $template = Template::withoutGlobalScopes()
                ->where('tenant_id', $customer->tenant_id)
                ->where('type', $date->type)
                ->where('is_default', true)
                ->first();

            if (! $template) {
                $this->warn("No default {$date->type} template for tenant {$customer->tenant_id}. Skipping {$customer->name}.");

                continue;
            }

            $message = $template->renderFor($customer, $date);

            if ($this->option('dry-run')) {
                $this->line("[DRY RUN] {$template->channel} → {$customer->name}: {$message}");

                continue;
            }

            $wishService->send($customer, $date, $template->channel, $message, $template);
            $this->line("Queued {$template->channel} wish for {$customer->name} ({$date->display_title}).");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
