<?php

return [
    'payment_received' => fn($tenant, $payment) =>
        "<b>✅ Payment Received</b>\nRoom: {$tenant->room->name}\nAmount: \${$payment->amount}\nMonth: {$payment->month_year}",

    'rent_due_soon' => fn($tenant) =>
        "⚠️ Reminder: Your rent for <b>{$tenant->room->name}</b> is due on {$tenant->due_date->format('d M')}.\nPlease make payment soon.",

    'rent_overdue' => fn($tenant) =>
        "❌ Your rent for <b>{$tenant->room->name}</b> is overdue since {$tenant->due_date->format('d M')}.\nPlease pay immediately to avoid penalty.",

    'lease_end_soon' => fn($tenant) =>
        "📅 Your lease for <b>{$tenant->room->name}</b> ends on {$tenant->lease_end->format('d M Y')}.\nPlease contact your landlord if you wish to renew.",

    'lease_expired' => fn($tenant) =>
        "🏁 Your lease for <b>{$tenant->room->name}</b> has expired.\nPlease contact your landlord.",
];
