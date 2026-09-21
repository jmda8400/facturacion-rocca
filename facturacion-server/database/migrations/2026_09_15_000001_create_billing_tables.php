<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arca_profiles', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('cuit', 11);
            $t->unsignedInteger('sales_point');
            $t->string('business_name');
            $t->string('address');
            $t->string('vat_condition')->default('IVA Responsable Inscripto');
            $t->string('gross_income')->nullable();
            $t->date('activity_started_at')->nullable();
            $t->string('certificate_path');
            $t->string('private_key_path');
            $t->string('ta_path');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('idempotency_key')->unique();
            $t->foreignId('arca_profile_id')->constrained();
            $t->string('external_reference')->index();
            $t->string('status')->default('pending');
            $t->string('invoice_type', 1);
            $t->json('request_payload');
            $t->unsignedBigInteger('voucher_number')->nullable();
            $t->string('cae')->nullable();
            $t->date('cae_expires_at')->nullable();
            $t->string('pdf_path')->nullable();
            $t->text('error')->nullable();
            $t->timestamp('emailed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('cache', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->mediumText('value');
            $t->integer('expiration');
        });
        Schema::create('cache_locks', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->string('owner');
            $t->integer('expiration');
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
        Schema::create('jobs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('queue')->index();
            $t->longText('payload');
            $t->unsignedTinyInteger('attempts');
            $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at');
            $t->unsignedInteger('created_at');
        });
        Schema::create('job_batches', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('name');
            $t->integer('total_jobs');
            $t->integer('pending_jobs');
            $t->integer('failed_jobs');
            $t->longText('failed_job_ids');
            $t->mediumText('options')->nullable();
            $t->integer('cancelled_at')->nullable();
            $t->integer('created_at');
            $t->integer('finished_at')->nullable();
        });
        Schema::create('failed_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('uuid')->unique();
            $t->text('connection');
            $t->text('queue');
            $t->longText('payload');
            $t->longText('exception');
            $t->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('arca_profiles');
    }
};
