<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles the Superuser creates (D-113): which kind of role it is, the name people see, the role it
 * started from (its manual follows that role) and who created it. The 17 roles BLUESKILLS ships
 * with are marked `system`.
 */
return new class extends Migration
{
    /** The built-in roles and their kind, as they were when this migration was written. */
    private const SYSTEM = [
        'superuser' => 'staff', 'verification_officer' => 'staff', 'trust_officer' => 'staff',
        'crm_agent' => 'staff', 'finance_officer' => 'staff',
        'owner' => 'employer', 'hr_admin' => 'employer', 'recruiter' => 'employer',
        'hiring_manager' => 'employer', 'viewer' => 'employer',
        'agency_owner' => 'agency', 'case_officer' => 'agency', 'compliance_reviewer' => 'agency',
        'institute_owner' => 'institute', 'trainer' => 'institute', 'assessor' => 'institute',
        'placement_officer' => 'institute',
    ];

    public function up(): void
    {
        $roles = config('permission.table_names.roles');

        Schema::table($roles, function (Blueprint $table) {
            $table->string('kind', 16)->nullable()->after('guard_name');
            $table->string('label', 60)->nullable()->after('kind');
            $table->string('description', 255)->nullable()->after('label');
            $table->string('based_on', 64)->nullable()->after('description');
            $table->boolean('system')->default(false)->after('based_on');
            $table->foreignUlid('created_by')->nullable()->after('system')->constrained('users')->nullOnDelete();
        });

        foreach (self::SYSTEM as $name => $kind) {
            DB::table($roles)->where('name', $name)->update(['kind' => $kind, 'system' => true]);
        }
    }

    public function down(): void
    {
        Schema::table(config('permission.table_names.roles'), function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['kind', 'label', 'description', 'based_on', 'system']);
        });
    }
};
