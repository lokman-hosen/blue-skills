<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulidMorphs('subject');
            $table->string('type', 32);
            $table->string('status', 16);
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 16)->nullable();
            $table->string('reason_code', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();

            $table->index(['type', 'status']);
            $table->index('status');
            $table->index('assigned_to');
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ulidMorphs('subject');
            $table->string('category', 32);
            $table->text('description');
            $table->string('status', 16);
            $table->timestampsTz();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('verification_requests');
    }
};
