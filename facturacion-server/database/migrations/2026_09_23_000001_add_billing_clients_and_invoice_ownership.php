<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_clients', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password_hash');
            $table->boolean('active')->default(true);
            $table->foreignId('default_arca_profile_id')->nullable()->constrained('arca_profiles')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('billing_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_client_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_idempotency_key_unique');
            $table->foreignId('billing_client_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('request_fingerprint', 64)->nullable()->after('idempotency_key');
            $table->unique(['billing_client_id', 'idempotency_key'], 'invoices_client_idempotency_unique');
        });

        Schema::create('billing_events', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('info');
            $table->string('event', 100)->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_events');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_client_idempotency_unique');
            $table->dropConstrainedForeignId('billing_client_id');
            $table->dropColumn('request_fingerprint');
            $table->unique('idempotency_key');
        });
        Schema::dropIfExists('billing_access_tokens');
        Schema::dropIfExists('billing_clients');
    }
};
