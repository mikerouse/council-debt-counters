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
        Schema::create('whistleblower_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('council_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->string('contact_email')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whistleblower_reports');
    }
};
