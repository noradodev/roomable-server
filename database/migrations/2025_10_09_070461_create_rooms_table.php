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
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('floor_id');
            $table->string('room_number');
            $table->string('room_type');
            $table->decimal('price', 10, 2);
            $table->uuid('current_tenant_id')->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('floor_id')->references('id')->on('floors')->onDelete('cascade');
            $table->foreign('current_tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
