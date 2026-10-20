<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type', 16);
            $table->string('legal_name');
            $table->string('display_name');
            $table->char('country_code', 2);
            $table->string('city')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('licence_number')->nullable();
            $table->date('licence_expires_on')->nullable();
            $table->string('verification_status', 32);
            $table->timestampTz('verified_at')->nullable();
            $table->string('data_region', 8)->default('ae');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('country_code')->references('code')->on('countries');
            $table->index('type');
            $table->index('verification_status');
            $table->index('country_code');
        });

        Schema::create('organization_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->foreignUlid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('joined_at')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 16)->nullable();
            $table->string('role', 64);
            $table->string('token_hash', 64)->unique();
            $table->foreignUlid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->index('organization_id');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
        Schema::dropIfExists('organization_members');
        Schema::dropIfExists('organizations');
    }
};
