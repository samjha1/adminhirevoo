<?php

namespace App\Modules\Rbac\Support;

final class PermissionCatalog
{
    /** @return list<array{slug: string, group: string, name: string, description?: string}> */
    public static function all(): array
    {
        return [
            ['slug' => 'leads.view', 'group' => 'leads', 'name' => 'View leads'],
            ['slug' => 'leads.view_all', 'group' => 'leads', 'name' => 'View all leads'],
            ['slug' => 'leads.create', 'group' => 'leads', 'name' => 'Create leads'],
            ['slug' => 'leads.import', 'group' => 'leads', 'name' => 'Import leads'],
            ['slug' => 'leads.export', 'group' => 'leads', 'name' => 'Export leads'],
            ['slug' => 'leads.assign_manager', 'group' => 'leads', 'name' => 'Assign to sales manager'],
            ['slug' => 'leads.assign_employee', 'group' => 'leads', 'name' => 'Assign to employee'],
            ['slug' => 'leads.reassign', 'group' => 'leads', 'name' => 'Reassign leads'],
            ['slug' => 'leads.release', 'group' => 'leads', 'name' => 'Release to pool'],
            ['slug' => 'leads.update_stage', 'group' => 'leads', 'name' => 'Update CRM stage'],
            ['slug' => 'leads.update_sales_status', 'group' => 'leads', 'name' => 'Update sales status'],
            ['slug' => 'leads.convert', 'group' => 'leads', 'name' => 'Mark lead converted'],
            ['slug' => 'leads.log_call', 'group' => 'leads', 'name' => 'Log calls'],
            ['slug' => 'leads.manage_followups', 'group' => 'leads', 'name' => 'Manage follow-ups'],
            ['slug' => 'leads.take_back', 'group' => 'leads', 'name' => 'Take back from employee'],
            ['slug' => 'consultations.view', 'group' => 'leads', 'name' => 'View consultations'],
            ['slug' => 'kanban.view', 'group' => 'leads', 'name' => 'View Kanban board'],
            ['slug' => 'staff.view', 'group' => 'staff', 'name' => 'View staff'],
            ['slug' => 'staff.manage', 'group' => 'staff', 'name' => 'Manage staff'],
            ['slug' => 'analytics.view', 'group' => 'analytics', 'name' => 'View analytics'],
            ['slug' => 'settings.view', 'group' => 'settings', 'name' => 'View settings'],
            ['slug' => 'rbac.manage_permissions', 'group' => 'rbac', 'name' => 'Manage roles & permissions'],
            ['slug' => 'applications.view', 'group' => 'platform', 'name' => 'View job applications'],
            ['slug' => 'platform.users', 'group' => 'platform', 'name' => 'Manage candidate users'],
            ['slug' => 'platform.employers', 'group' => 'platform', 'name' => 'Manage employers'],
            ['slug' => 'platform.jobs', 'group' => 'platform', 'name' => 'Manage jobs'],
            ['slug' => 'platform.referrals', 'group' => 'platform', 'name' => 'Manage referrals'],
            ['slug' => 'platform.payments', 'group' => 'platform', 'name' => 'View payments'],
            ['slug' => 'platform.sponsored_ads', 'group' => 'platform', 'name' => 'Moderate sponsored ads'],
        ];
    }

    /** @return array<string, list<string>> */
    public static function rolePermissionMap(): array
    {
        $all = array_column(self::all(), 'slug');

        return [
            'super_admin' => $all,
            'admin' => array_values(array_filter($all, fn (string $s) => $s !== 'rbac.manage_permissions')),
            'marketing' => [
                'leads.view', 'leads.view_all', 'leads.create', 'leads.import', 'leads.export',
                'leads.assign_manager', 'leads.reassign', 'leads.release', 'consultations.view',
                'analytics.view', 'applications.view',
            ],
            'sales_manager' => [
                'leads.view', 'leads.assign_employee', 'leads.reassign', 'leads.update_stage',
                'leads.update_sales_status', 'leads.log_call', 'leads.manage_followups',
                'leads.take_back', 'kanban.view', 'staff.view', 'staff.manage', 'analytics.view',
                'applications.view',
            ],
            'sales_employee' => [
                'leads.view', 'leads.update_stage', 'leads.update_sales_status',
                'leads.log_call', 'leads.manage_followups', 'kanban.view', 'analytics.view',
                'applications.view',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function legacyRoleMap(): array
    {
        return [
            'super_admin' => 'super_admin',
            'admin' => 'admin',
            'marketing' => 'marketing',
            'sales_manager' => 'sales_manager',
            'sales_employee' => 'sales_employee',
        ];
    }
}
