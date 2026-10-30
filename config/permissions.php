<?php

/*
|--------------------------------------------------------------------------
| Roles & permissions editor (D-112)
|--------------------------------------------------------------------------
|
| Every permission the Superuser can grant, grouped the way the editor shows
| them: group -> module -> actions. Permission names come from
| App\Domain\Identity\Enums\Permission. `kinds` says which kinds of role the
| module applies to: staff, employer, agency or institute. Labels are
| translation keys: `permission_group.{group}`, `permission_module.{module}`,
| `permission.{name}` and `permission_hint.{name}`.
|
| Add a permission here whenever a new one is added to the enum; a test fails
| until every enum case is listed.
|
*/

return [

    // Every staff role keeps it: it marks a staff account. It is shown, but can't be changed.
    'staff_access_permission' => 'admin.access',

    // Only the Superuser may hold these. Managing staff lets someone make themselves Superuser.
    'superuser_only' => ['staff.manage'],

    // Each kind of owner keeps these, so an organization always has someone who can manage it.
    'owner_locked' => ['team.manage'],

    'groups' => [
        'organization' => ['kinds' => ['employer', 'agency', 'institute'], 'modules' => [
            'organization' => ['update'],
            'team' => ['manage'],
            'billing' => ['manage'],
        ]],
        'hiring' => ['kinds' => ['employer', 'agency'], 'modules' => [
            'jobs' => ['view', 'manage', 'submit'],
            'applications' => ['view', 'manage'],
            'interviews' => ['manage'],
            'projects' => ['manage'],
        ]],
        'talent' => ['kinds' => ['employer', 'agency', 'institute'], 'modules' => [
            'talent' => ['search'],
            'shortlists' => ['manage'],
            'talent_pools' => ['manage'],
        ]],
        'agency' => ['kinds' => ['employer', 'agency'], 'modules' => [
            'mandates' => ['manage', 'confirm'],
            'compliance' => ['review'],
        ]],
        'mobility' => ['kinds' => ['employer', 'agency'], 'modules' => [
            'mobility' => ['manage'],
        ]],
        'institute' => ['kinds' => ['institute'], 'modules' => [
            'courses' => ['manage'],
            'cohorts' => ['manage'],
            'graduates' => ['invite'],
            'attestations' => ['issue'],
            'assessments' => ['record'],
            'placements' => ['view'],
        ]],
        'staff_reviews' => ['kinds' => ['staff'], 'modules' => [
            'admin' => ['access'],
            'verifications' => ['review'],
            'jobs' => ['moderate'],
            'skill_shows' => ['moderate'],
        ]],
        'staff_operations' => ['kinds' => ['staff'], 'modules' => [
            'trust' => ['manage'],
            'crm' => ['manage'],
            'finance' => ['manage'],
        ]],
        'staff_platform' => ['kinds' => ['staff'], 'modules' => [
            'config' => ['manage'],
            'taxonomy' => ['manage'],
            'staff' => ['manage'],
            'horizon' => ['view'],
        ]],
    ],

    /*
    | Some modules exist for one kind of organization only, even inside a shared group:
    | recruitment projects are for employers, talent pools for institutes, mandate requests
    | for agencies (employers confirm them) and compliance for agencies.
    */
    'only' => [
        'projects.manage' => ['employer'],
        'billing.manage' => ['employer', 'agency'],
        'talent.search' => ['employer', 'agency'],
        'shortlists.manage' => ['employer', 'agency'],
        'talent_pools.manage' => ['institute'],
        'mandates.manage' => ['agency'],
        'mandates.confirm' => ['employer'],
        'compliance.review' => ['agency'],
    ],
];
