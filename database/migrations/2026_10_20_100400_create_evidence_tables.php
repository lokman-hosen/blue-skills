<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['owner_user_id', 'type']);
            $table->index('expires_at');
        });

        Schema::create('evidence_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            // What the evidence is about or backed by: a document, worker skill, experience…
            $table->nullableUlidMorphs('documentable');
            $table->string('status', 32);
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->string('reason_code', 64)->nullable();
            $table->timestampsTz();

            $table->index(['worker_profile_id', 'type']);
            $table->index('status');
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 32);
            $table->nullableUlidMorphs('subject');
            $table->jsonb('scope')->nullable();
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->index(['worker_profile_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
        Schema::dropIfExists('evidence_items');
        Schema::dropIfExists('documents');
    }
};
