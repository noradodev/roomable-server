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
        Schema::create('landlord_payment_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->constrained('landlord_payment_methods')->cascadeOnDelete();
            $table->text('instructions')->nullable();
            $table->string('collector_name')->nullable();
            $table->text('collection_location')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->timestamps();

            $table->unique('payment_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_payment_configurations');
    }
};
