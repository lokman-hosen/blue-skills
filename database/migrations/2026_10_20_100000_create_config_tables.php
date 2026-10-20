<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('code', 2)->unique();
            $table->string('name');
            $table->char('currency', 3);
            $table->string('dial_code', 6);
            $table->boolean('enabled_as_source')->default(false)->index();
            $table->boolean('enabled_as_destination')->default(false)->index();
            $table->timestampsTz();
        });

        Schema::create('country_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('country_code', 2);
            $table->string('key', 64);
            $table->jsonb('value');
            $table->timestampsTz();

            $table->foreign('country_code')->references('code')->on('countries')->cascadeOnDelete();
            $table->unique(['country_code', 'key']);
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 10)->unique();
            $table->string('native_name');
            $table->string('english_name');
            $table->boolean('rtl')->default(false);
            $table->string('status', 16)->index();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
        Schema::dropIfExists('country_rules');
        Schema::dropIfExists('countries');
    }
};
