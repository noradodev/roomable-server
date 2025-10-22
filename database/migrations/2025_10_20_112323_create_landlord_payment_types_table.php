<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('landlord_payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'cash', 'qr_code'
            $table->string('name'); // 'Cash Payment', 'QR Code'
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_payment_types');
    }
};
