<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 3: when a job was last submitted for review and when a material change last sent it
     * back (product rule 5). Review decisions themselves live on verification_requests (D-054).
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('material_change_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['submitted_at', 'material_change_at']);
        });
    }
};
