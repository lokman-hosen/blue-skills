<?php

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;
use Illuminate\Support\Facades\Broadcast;

// Live updates (Module 5, D-067). Payloads carry ids only; pages reload what the viewer may see.

// Personal channel: notifications and the worker's message badge.
Broadcast::channel('users.{id}', fn (User $user, string $id) => $user->id === $id);

// An open thread.
Broadcast::channel('conversations.{conversation}', fn (User $user, Conversation $conversation) => $user->can('view', $conversation));

// The organization inbox badge.
Broadcast::channel('organizations.{organization}.inbox', fn (User $user, Organization $organization) => OrganizationPermissions::allows($user, $organization, Permission::ApplicationsView));
