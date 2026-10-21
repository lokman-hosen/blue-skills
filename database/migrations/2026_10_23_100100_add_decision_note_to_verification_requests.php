<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The reviewer's message to the organization, kept apart from `notes` (intake flags such as
     * a free-webmail sign-up, D-041).
     */
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->text('decision_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->dropColumn('decision_note');
        });
    }
};
