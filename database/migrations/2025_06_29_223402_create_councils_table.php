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
        Schema::create('councils', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('population')->nullable();
            $table->unsignedBigInteger('households')->nullable();
            $table->decimal('current_liabilities', 15, 2)->nullable();
            $table->decimal('long_term_liabilities', 15, 2)->nullable();
            $table->decimal('finance_lease_pfi_liabilities', 15, 2)->nullable();
            $table->decimal('manual_debt_entry', 15, 2)->nullable();
            $table->decimal('non_council_tax_income', 15, 2)->nullable();
            $table->decimal('council_tax_general_grants_income', 15, 2)->nullable();
            $table->decimal('government_grants_income', 15, 2)->nullable();
            $table->decimal('all_other_income', 15, 2)->nullable();
            $table->decimal('total_debt', 15, 2)->nullable();
            $table->decimal('total_income', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('councils');
    }
};
