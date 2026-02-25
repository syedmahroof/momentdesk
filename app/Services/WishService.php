<?php

namespace App\Services;

use App\Jobs\SendWishJob;
use App\Models\Customer;
use App\Models\CustomerDate;
use App\Models\MessageLog;
use App\Models\Template;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;

class WishService
{
    public function send(
        Customer $customer,
        CustomerDate $date,
        string $channel,
        string $message,
        ?Template $template = null
    ): MessageLog {
        $log = MessageLog::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'customer_date_id' => $date->id,
            'template_id' => $template?->id,
            'channel' => $channel,
            'message' => $message,
            'recipient' => $this->getRecipient($customer, $channel),
            'status' => 'pending',
        ]);

        SendWishJob::dispatch($log);

        return $log;
    }

    public function sendNow(MessageLog $log): void
    {
        try {
            match ($log->channel) {
                'email' => $this->sendEmail($log),
                'sms' => $this->sendSms($log),
                'whatsapp' => $this->sendWhatsApp($log),
            };

            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('WishService send failed', ['log_id' => $log->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateWhatsAppLink(Customer $customer, string $message): string
    {
        $number = preg_replace('/[^0-9]/', '', $customer->whatsapp_number ?? $customer->phone ?? '');

        return 'https://wa.me/'.$number.'?text='.urlencode($message);
    }

    private function sendEmail(MessageLog $log): void
    {
        if (! $log->customer->email) {
            throw new \RuntimeException('Customer has no email address.');
        }

        Mail::raw($log->message, function ($mail) use ($log) {
            $mail->to($log->customer->email, $log->customer->name)
                ->subject($log->template?->subject ?? 'Special Wishes for You!');
        });
    }

    private function sendSms(MessageLog $log): void
    {
        if (! $log->customer->phone) {
            throw new \RuntimeException('Customer has no phone number.');
        }

        $twilio = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $twilio->messages->create($log->customer->phone, [
            'from' => config('services.twilio.from'),
            'body' => $log->message,
        ]);
    }

    private function sendWhatsApp(MessageLog $log): void
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');

        if (! $token || ! $phoneId) {
            // Manual WhatsApp via wa.me link — mark as sent (link was already shown to user)
            return;
        }

        $phone = preg_replace('/[^0-9]/', '', $log->customer->whatsapp_number ?? $log->customer->phone ?? '');

        if (! $phone) {
            throw new \RuntimeException('Customer has no WhatsApp number.');
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => $log->message],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('WhatsApp API error: '.$response->body());
        }
    }

    private function getRecipient(Customer $customer, string $channel): string
    {
        return match ($channel) {
            'email' => $customer->email ?? '',
            'sms' => $customer->phone ?? '',
            'whatsapp' => $customer->whatsapp_number ?? $customer->phone ?? '',
        };
    }
}
