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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('landlord_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('telegram_id')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->enum('status', ['unassigned', 'active', 'inactive', 'moved_out'])->default('unassigned');
            $table->date('move_in_date');
            $table->date('due_date')->nullable();
            $table->enum('rent_status', ['on_time', 'due_soon', 'overdue'])->default('on_time');
            $table->date('move_out_date')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
