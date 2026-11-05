<?php

namespace App\Console\Commands;

use App\Mail\AbandonedCartReminder;
use App\Models\AbandonedCheckout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAbandonedReminders extends Command
{
    protected $signature = 'nexcart:send-abandoned-reminders';

    protected $description = 'E-mail customers who typed their e-mail at checkout but never ordered (runs hourly)';

    public function handle(): int
    {
        if (! setting('growth.abandoned', true)) {
            return self::SUCCESS;
        }

        $hours = max(1, (int) setting('growth.abandoned_hours', 2));
        $due = AbandonedCheckout::whereNull('reminded_at')->whereNull('recovered_at')->where('opted_out', false)
            ->where('items_count', '>', 0)
            ->where('last_seen_at', '<=', now()->subHours($hours))->where('last_seen_at', '>=', now()->subDays(7))->limit(200)->get();

        $sent = 0;
        foreach ($due as $cart) {
            try {
                Mail::to($cart->email)->locale($cart->locale ?: config('app.locale'))->send(new AbandonedCartReminder($cart));
                $cart->update(['reminded_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Abandoned-cart e-mail failed: '.$e->getMessage());
            }
        }

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
