<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arca_profiles', function (Blueprint $table) {
            $table->unsignedInteger('sales_point')->nullable()->change();
            $table->string('business_name')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('vat_condition')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('arca_profiles', function (Blueprint $table) {
            $table->unsignedInteger('sales_point')->nullable(false)->change();
            $table->string('business_name')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
            $table->string('vat_condition')->nullable(false)->default('IVA Responsable Inscripto')->change();
        });
    }
};
