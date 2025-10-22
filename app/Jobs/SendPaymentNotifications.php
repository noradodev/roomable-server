<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPaymentNotifications implements ShouldQueue
{
    use Queueable;
    public $paymentId;
    /**
     * Create a new job instance.
     */
    public function __construct($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegram): void
    {
        $payment = Payment::with('tenant.room.floor.property.user.profile')->find($this->paymentId);
        if (!$payment) return;

        $tenant = $payment->tenant;
        $parseDateTime = Carbon::parse($payment->paid_at);
        $parseYearsMonth = Carbon::parse($payment->month_years);
        $statusEmoji = $payment->status === 'paid' ? '✅' : '🕒';
        $statusText = strtoupper($payment->status);

        $message =
            "💰 <b>Payment {$statusText}</b> {$statusEmoji}\n\n" .
            "🏠 <b>Room:</b> {$tenant->room->room_number}\n" .
            "👤 <b>Tenant:</b> {$tenant->name}\n" .
            "💵 <b>Total:</b> 💲" . number_format($payment->total_amount, 2) . "\n" .
            "📅 <b>Month:</b> {$payment->month_years}\n" .
            "⏰ <b>Paid At:</b> " . \Carbon\Carbon::parse($payment->paid_at)->format('d-M-Y H:i:s');

        $formattedDateTime = $parseDateTime->format('d-M-Y H:i:s');
        $formattedMonthYears = $parseYearsMonth->format('Y-M');
        if ($tenant->telegram_chat_id) {
            $telegram->sendMessage(
                $tenant->telegram_chat_id,
                $message
            );
        }

        $landlordChatId = $tenant->room->floor->property->user->profile->telegram_chat_id ?? null;
        if ($landlordChatId) {
            $telegram->sendMessage(
                $landlordChatId,
                "📅 <b>Payment Received — {$formattedMonthYears}</b>\n\n" .
                    "👤 <b>Tenant:</b> {$tenant->name}\n" .
                    "🏠 <b>Room:</b> {$tenant->room->room_number}\n" .
                    "💵 <b>Total Paid:</b> 💲" . number_format($payment->total_amount, 2) . "\n" .
                    "⏰ <b>Paid At:</b> {$formattedDateTime}",
            );
        }
    }
}
