<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 5: application-linked conversations, in-app notifications, delivery preferences, the
     * WhatsApp / SMS / email delivery log, and invitations to apply (spec §9).
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // One conversation per application, between the worker and the posting organization.
            $table->foreignUlid('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('last_message_at')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'last_message_at']);
            $table->index(['worker_profile_id', 'last_message_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            // worker | organization | system
            $table->string('side', 16);
            $table->text('body');
            // Microseconds, so a reply in the same second as a read still counts as unread.
            $table->timestampsTz(6);

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('conversation_reads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('last_read_at', 6);
            $table->timestampsTz();

            $table->unique(['conversation_id', 'user_id']);
        });

        // Laravel's notifications table, with ULID keys for notifiables.
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->ulidMorphs('notifiable');
            $table->jsonb('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('event', 48);
            $table->string('channel', 16);
            $table->boolean('enabled');
            $table->timestampsTz();

            $table->unique(['user_id', 'event', 'channel']);
        });

        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 16);
            // Encrypted: phone numbers and email addresses are personal data.
            $table->text('recipient');
            $table->string('event', 48);
            $table->string('template', 64)->nullable();
            // Encrypted event parameters and link, rendered in the recipient's language when sent.
            $table->text('payload');
            $table->string('status', 16);
            $table->string('provider_id')->nullable();
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('send_after')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'send_after']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('job_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 300)->nullable();
            $table->string('status', 16);
            $table->timestampTz('responded_at')->nullable();
            $table->timestampsTz();

            $table->unique(['job_id', 'worker_profile_id']);
            $table->index(['worker_profile_id', 'status']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_invitations');
        Schema::dropIfExists('outbound_messages');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('conversation_reads');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
