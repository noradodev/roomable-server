<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TelegramService;
use Illuminate\Console\Command;


class CheckRentStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rent:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check rent due, overdue, and lease expiry, and send Telegram alerts';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram)
    {
        $today = now()->startOfDay();
        $dueSoonStart = $today->copy()->toDateString();
        $dueSoonEnd = $today->copy()->addDays(3)->toDateString();
        info($today->toDateString());

        $dueSoonTenants = Tenant::with('room')
            ->whereHas('payments', function ($query) use ($today) {
                $query->where('month_years', $today->format('Y-m'))
                    ->whereNull('status');
            }, '<', 1)
            ->whereBetween('due_date', [$today->toDateString(), $dueSoonEnd])
            ->get();
        if ($dueSoonTenants->isNotEmpty()) {
            // We check how many days are LEFT by putting the future date ($due_date) first.
            info('DueSoon check: ' . $dueSoonTenants[0]->due_date->diffInDays($today) . ' days remaining for the first tenant.');
        }

        foreach ($dueSoonTenants as $tenant) {

            $daysOverdue = $today->diffInDays($tenant->due_date);
            $message = "⚠️ Hello {$tenant->name}, your rent due in $daysOverdue day(s) for Room {$tenant->room->room_number}. Due date: {$tenant->due_date->format('d/m/Y')}";

            if ($tenant->telegram_chat_id) {
                $telegram->sendMessage($tenant->telegram_chat_id, $message);
            }
        }

        $overdueTenants = Tenant::with('room')
            ->whereHas('payments', function ($query) use ($today) {
                $query->where('month_years', '<=', $today->format('Y-m'))
                    ->where('status', '!=', 'paid');
            })
            ->where('due_date', '<', $today)
            ->get();

        info('Overdue check: ' . $overdueTenants . ' tenants found.');


        foreach ($overdueTenants as $tenant) {
            $daysOverdue = $today->diffInDays($tenant->due_date);
            if ($tenant->telegram_chat_id) {
                app(TelegramService::class)->sendMessage(
                    $tenant->telegram_chat_id,
                    "❗ Rent overdue by $daysOverdue day(s) for Room {$tenant->room->room_number}. Due date: {$tenant->due_date->format('d/m/Y')}"
                );
            }
        }
        $this->info('Rent and lease notifications sent successfully.');
    }
}
