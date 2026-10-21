<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Document events in the case history name the checklist item they are about (D-079).
     */
    public function up(): void
    {
        Schema::table('mobility_case_events', function (Blueprint $table) {
            $table->foreignUlid('case_document_id')->nullable()->after('stage_key')->constrained('case_documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mobility_case_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('case_document_id');
        });
    }
};
