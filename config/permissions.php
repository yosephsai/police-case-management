<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application roles
    |--------------------------------------------------------------------------
    |
    | These are proposed software roles, not police ranks or legal authority.
    | Add or rename roles here; keep slugs stable once data exists.
    |
    */

    'roles' => [
        'admin' => 'System Administrator',
        'station_commander' => 'Station Commander',
        'reporting_officer' => 'Reporting Officer',
        'reviewing_officer' => 'Reviewing Officer',
        'supervisor' => 'Investigation Supervisor',
        'lead_detective' => 'Lead Detective',
        'investigator' => 'Assistant Investigator',
        'records_officer' => 'Records Officer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission map (defaults)
    |--------------------------------------------------------------------------
    |
    | Map each capability to the roles that hold it by default. Administrators
    | may override these defaults at runtime (stored in `permission_overrides`).
    | Controllers and views must check these permission names, never roles.
    |
    */

    'permissions' => [
        'manage-staff' => ['admin'],
        'manage-permissions' => ['admin'],

        'register-complaints' => ['reporting_officer'],

        'view-all-complaints' => ['admin', 'station_commander', 'reviewing_officer', 'supervisor', 'records_officer'],
        'view-assigned-complaints' => ['lead_detective', 'investigator'],

        'view-audit-logs' => ['admin', 'station_commander', 'records_officer', 'supervisor'],
        'view-reports' => ['admin', 'station_commander', 'records_officer', 'supervisor'],

        'review-complaints' => ['reviewing_officer'],
        'create-cases' => ['reviewing_officer', 'supervisor', 'station_commander'],

        'view-all-cases' => ['admin', 'station_commander', 'records_officer', 'supervisor', 'reviewing_officer'],
        'assign-cases' => ['supervisor', 'station_commander'],
        'update-case-status' => ['supervisor', 'station_commander'],

        'record-activities' => ['lead_detective', 'investigator'],
        'add-evidence' => ['lead_detective', 'investigator'],
        'handle-evidence' => ['lead_detective', 'investigator', 'records_officer'],
        'manage-people' => ['lead_detective', 'investigator'],
        'submit-report' => ['lead_detective'],
        'review-report' => ['supervisor', 'station_commander'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Human-readable permission labels (used by the permission editor)
    |--------------------------------------------------------------------------
    */

    'labels' => [
        'manage-staff' => 'Manage staff accounts',
        'manage-permissions' => 'Manage permissions',
        'register-complaints' => 'Register complaints',
        'view-all-complaints' => 'View all complaints',
        'view-assigned-complaints' => 'View complaints for assigned cases',
        'view-audit-logs' => 'View audit log',
        'view-reports' => 'View/export reports',
        'review-complaints' => 'Review complaints',
        'create-cases' => 'Convert complaints to cases',
        'view-all-cases' => 'View all cases (not only assigned)',
        'assign-cases' => 'Assign investigators',
        'update-case-status' => 'Update case status',
        'record-activities' => 'Record investigation activities',
        'add-evidence' => 'Add evidence records',
        'handle-evidence' => 'Record evidence handling',
        'manage-people' => 'Manage case people',
        'submit-report' => 'Submit investigation reports',
        'review-report' => 'Review investigation reports',
    ],

];
