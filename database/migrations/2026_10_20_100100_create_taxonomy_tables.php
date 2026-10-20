<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sector → Occupation → Specialization → Skill (spec §12). `name` holds
     * translations keyed by locale, e.g. {"en": "Electrician", "bn": "ইলেকট্রিশিয়ান"}.
     */
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug')->unique();
            $table->jsonb('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('occupations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sector_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->jsonb('name');
            $table->timestampsTz();

            $table->index('sector_id');
        });

        Schema::create('specializations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('occupation_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->jsonb('name');
            $table->timestampsTz();

            $table->unique(['occupation_id', 'slug']);
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('occupation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->jsonb('name');
            $table->timestampsTz();

            $table->index('occupation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
        Schema::dropIfExists('specializations');
        Schema::dropIfExists('occupations');
        Schema::dropIfExists('sectors');
    }
};
