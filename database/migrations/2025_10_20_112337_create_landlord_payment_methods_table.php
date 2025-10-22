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
        Schema::create('landlord_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('landlord_id')->constrained('users');
            $table->foreignId('payment_type_id')->constrained('landlord_payment_types');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['landlord_id', 'payment_type_id'], 'lp_methods_landlord_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_payment_methods');
    }
};
