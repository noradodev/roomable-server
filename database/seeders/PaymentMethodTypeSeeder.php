<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methodTypes = [
            [
                'code' => 'cash',
                'name' => 'Cash Payment',
                'is_required' => true,
                'is_active' => true,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'qr_code',
                'name' => 'QR Code Payment',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'bakong_qr_code',
                'name' => 'Automatic bakong qr code',
                'is_required' => false,
                'is_active' => false, // Disabled by default
                'display_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($methodTypes as $type) {
            DB::table('landlord_payment_types')->updateOrInsert(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
