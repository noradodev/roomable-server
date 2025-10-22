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
        Schema::create('landlord_payment_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_payment_method_id')->constrained('landlord_payment_methods')->cascadeOnDelete();
            $table->string('file_type');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('file_url');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_payment_files');
    }
};
