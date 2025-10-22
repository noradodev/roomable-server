<?php

namespace App\Console\Commands;

use App\Events\PaymentCreated;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreatePendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-pending-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private function getLandlordPaymentKeyboard(string $paymentId): array
    {
        $url = config('app.front_url') . "/payments/view/{$paymentId}";
        // frontend route (Laravel’s url() helper)
        return [
            'inline_keyboard' => [
                [
                    ['text' => '🧾 Add Utility Costs', 'url' => $url],
                ],
            ],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram)
    {
        $today = Carbon::now()->startOfDay();
        $currentMonthYear = $today->format('Y-m');

        $activeTenantsDueToday = Tenant::whereDay('due_date', $today->day)
            ->where('status', 'active')
            ->with('room.floor.property.user.profile')
            ->get();
        $this->info("Checking for tenants due on day: {$today->day}. Found {$activeTenantsDueToday->count()} active tenants.");

        foreach ($activeTenantsDueToday as $tenant) {
            $existingPayment = Payment::where('tenant_id', $tenant->id)
                ->where('month_years', $currentMonthYear)
                ->first();

            if ($existingPayment) {
                $this->comment("Payment already exists for Tenant ID: {$tenant->id} ({$currentMonthYear})");
                continue;
            }

            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->room->id,
                'amount' => $tenant->room->price,
                'month_years' => $currentMonthYear,
                'status' => 'pending',
            ]);
            broadcast(new PaymentCreated($payment));

            $landlordChatId = $tenant->room->floor->property->user->profile->telegram_chat_id ?? null;
            if (!$landlordChatId) {
                $this->warn("Landlord chat ID missing for tenant {$tenant->id}");
                continue;
            }

            $keyboard = $this->getLandlordPaymentKeyboard($payment->id);

            $message = "📅 <b>New Pending Payment</b>\n\n"
                . "👤 <b>Tenant:</b> {$tenant->name}\n"
                . "🏠 <b>Room:</b> {$tenant->room->room_number}\n"
                . "💵 <b>Base Rent:</b> $" . number_format($tenant->room->price, 2) . "\n"
                . "🗓 <b>Month:</b> {$currentMonthYear}\n\n"
                . "👉 Tap below to add water/electricity costs:";

            $response = $telegram->sendMessage(
                $landlordChatId,
                $message,
                [
                    'parse_mode' => 'HTML',
                    'reply_markup' => $this->getLandlordPaymentKeyboard($payment->id)
                ]
            );

            $body = $response?->json();

            if (isset($body['result']['message_id'])) {
                $payment->telegram_message_id = $body['result']['message_id'];
                $payment->save();
                $this->info("Saved Telegram message_id: {$payment->telegram_message_id}");
            }

            $this->info("Pending payment created and landlord notified for Tenant ID: {$tenant->id}");


            $this->info("Sending to landlord: '{$landlordChatId}' (" . gettype($landlordChatId) . ")");
            $this->info("Pending payment created and landlord notified for Tenant ID: {$tenant->id}");
        }

        $this->info('Pending payments creation complete.');
        return Command::SUCCESS;
    }
}
