<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('voucher_date')->nullable()->after('voucher_number');
            $table->unique(['arca_profile_id', 'invoice_type', 'voucher_number'], 'invoices_fiscal_voucher_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_fiscal_voucher_unique');
            $table->dropColumn('voucher_date');
        });
    }
};
