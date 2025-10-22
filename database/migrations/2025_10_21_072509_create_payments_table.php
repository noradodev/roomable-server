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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('landlord_payment_method_id')
                ->nullable()
                ->constrained('landlord_payment_methods');
            $table->bigInteger('telegram_message_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('month_years');
            $table->decimal('electricity_cost', 10, 2)->nullable();
            $table->decimal('water_cost', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->enum('method', ['cash', 'bank', 'qr', 'other']);
            $table->enum('status', ['pending', 'awaiting_tenant', 'awaiting_confirmation', 'paid', 'rejected', 'late'])->default('pending');
            $table->string('proof_url')->nullable();
            $table->string('note')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
