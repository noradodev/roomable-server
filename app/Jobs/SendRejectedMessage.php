<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;

class SendRejectedMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $paymentId;

    public function __construct(int $paymentId)
    {
        $this->paymentId = $paymentId;
    }

    public function handle(TelegramService $telegram): void
    {
        $payment = Payment::with('tenant.room.floor.property.user.profile')->find($this->paymentId);
        
        if (!$payment || !$payment->tenant) return;

        $tenant = $payment->tenant;
        $formattedMonthYears = Carbon::parse($payment->month_years)->format('Y-M');


        $tenantMessage = '';
        $landlordMessage = '';

        if ($payment->status === 'paid') {
            
            if (!$payment->paid_at) {
                 logger()->error("Payment ID {$this->paymentId} status 'paid' but 'paid_at' is null.");
                 return; 
            }
            $formattedPaidAt = Carbon::parse($payment->paid_at)->format('d-M-Y H:i:s');
            
            $tenantMessage =
                "✅ <b>Payment Confirmed!</b>\n\n" .
                "Great news, {$tenant->name}! Your payment for {$payment->month_years} has been **confirmed and recorded**.\n\n" .
                "💵 <b>Total Paid:</b> 💲" . number_format($payment->total_amount, 2) . "\n" .
                "⏰ <b>Confirmed At:</b> {$formattedPaidAt}";
            
            $landlordMessage = 
                "✅ <b>Payment Confirmed — {$formattedMonthYears}</b>\n\n" .
                "👤 <b>Tenant:</b> {$tenant->name}\n" .
                "🏠 <b>Room:</b> {$tenant->room->room_number}\n" .
                "💵 <b>Total Paid:</b> 💲" . number_format($payment->total_amount, 2) . "\n" .
                "⏰ <b>Confirmed At:</b> {$formattedPaidAt}";

        } else if ($payment->status === 'awaiting_tenant' && $payment->rejection_reason) {
            
            $rejectionReason = $payment->rejection_reason ?? 'The submitted proof was invalid or incomplete.';

            $tenantMessage =
                "❌ <b>Payment Claim Rejected</b>\n\n" .
                "Hello {$tenant->name}. Your recent payment claim for {$payment->month_years} has been **rejected** by the landlord.\n\n" .
                "💡 <b>Reason:</b> {$rejectionReason}\n\n" .
                "Please submit a new, correct proof of payment as soon as possible.";
            
            $landlordMessage = 
                "❌ <b>Payment Rejected — {$formattedMonthYears}</b>\n\n" .
                "The payment claim from {$tenant->name} ({$tenant->room->room_number}) has been **rejected**.\n" .
                "Reason: {$rejectionReason}";

        } else {
            
            $tenantMessage =
                "🕒 <b>Claim Submitted</b>\n\n" .
                "Thank you! Your payment claim for {$payment->month_years} is now with the landlord for review. You will receive a confirmation shortly.";
            
            $landlordMessage = 
                "🔔 <b>New Payment Claim — {$formattedMonthYears}</b>\n\n" .
                "A new payment claim has been submitted by **{$tenant->name}** ({$tenant->room->room_number}).\n" .
                "💵 <b>Amount:</b> 💲" . number_format($payment->total_amount, 2) . "\n\n" .
                "Please review the proof of payment in the dashboard.";
        }



        if ($tenant->telegram_chat_id && $tenantMessage) {
            $telegram->sendMessage(
                $tenant->telegram_chat_id,
                $tenantMessage
            );
        }

        $landlordChatId = $tenant->room->floor->property->user->profile->telegram_chat_id ?? null;
        if ($landlordChatId && $landlordMessage) {
            $telegram->sendMessage(
                $landlordChatId,
                $landlordMessage
            );
        }
    }
}