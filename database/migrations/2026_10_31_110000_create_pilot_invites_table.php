<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pilot invite codes (D-108): while BLUESKILLS_PILOT is on, an organization registers with
     * a code staff issued to a selected partner.
     */
    public function up(): void
    {
        Schema::create('pilot_invites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 16)->unique();
            // Who the code is for, e.g. the partner company; staff-facing only.
            $table->string('partner_name', 160);
            // employer | agency | institute; null lets any type use it.
            $table->string('organization_type', 16)->nullable();
            $table->unsignedSmallInteger('max_uses')->default(1);
            $table->unsignedSmallInteger('used_count')->default(0);
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignUlid('pilot_invite_id')->nullable()->constrained('pilot_invites')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pilot_invite_id');
        });

        Schema::dropIfExists('pilot_invites');
    }
};
