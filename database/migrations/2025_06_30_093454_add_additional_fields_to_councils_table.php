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
        Schema::table('councils', function (Blueprint $table) {
            $table->string('council_type')->nullable();
            $table->string('council_location')->nullable();
            $table->decimal('minimum_revenue_provision', 15, 2)->nullable();
            $table->decimal('annual_spending', 15, 2)->nullable();
            $table->decimal('annual_deficit', 15, 2)->nullable();
            $table->decimal('interest_paid', 15, 2)->nullable();
            $table->decimal('capital_financing_requirement', 15, 2)->nullable();
            $table->decimal('usable_reserves', 15, 2)->nullable();
            $table->decimal('consultancy_spend', 15, 2)->nullable();
            $table->unsignedInteger('waste_report_count')->nullable();
            $table->string('statement_of_accounts')->nullable();
            $table->string('financial_data_source_url')->nullable();
            $table->string('status_message')->nullable();
            $table->string('status_message_type')->nullable();
            $table->boolean('council_closed')->default(false);
            $table->json('debt_adjustments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('councils', function (Blueprint $table) {
            $table->dropColumn([
                'council_type',
                'council_location',
                'minimum_revenue_provision',
                'annual_spending',
                'annual_deficit',
                'interest_paid',
                'capital_financing_requirement',
                'usable_reserves',
                'consultancy_spend',
                'waste_report_count',
                'statement_of_accounts',
                'financial_data_source_url',
                'status_message',
                'status_message_type',
                'council_closed',
                'debt_adjustments',
            ]);
        });
    }
};
