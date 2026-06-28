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
            ['slug' => 'analytics.view_executive', 'group' => 'analytics', 'name' => 'View executive dashboard (both pipelines)'],
            ['slug' => 'analytics.export', 'group' => 'analytics', 'name' => 'Export dashboard reports'],
            ['slug' => 'audit.view', 'group' => 'rbac', 'name' => 'View audit logs'],
            ['slug' => 'settings.view', 'group' => 'settings', 'name' => 'View settings'],
            ['slug' => 'rbac.manage_permissions', 'group' => 'rbac', 'name' => 'Manage roles & permissions'],
            ['slug' => 'applications.view', 'group' => 'platform', 'name' => 'View job applications'],
            ['slug' => 'platform.users', 'group' => 'platform', 'name' => 'Manage candidate users'],
            ['slug' => 'platform.employers', 'group' => 'platform', 'name' => 'Manage employers'],
            ['slug' => 'platform.jobs', 'group' => 'platform', 'name' => 'Manage jobs'],
            ['slug' => 'platform.referrals', 'group' => 'platform', 'name' => 'Manage referrals'],
            ['slug' => 'platform.payments', 'group' => 'platform', 'name' => 'View payments'],
            ['slug' => 'employer_payments.view', 'group' => 'platform', 'name' => 'View employer plan payments'],
            ['slug' => 'employer_payments.complete', 'group' => 'platform', 'name' => 'Verify employer plan cheques'],
            ['slug' => 'platform.sponsored_ads', 'group' => 'platform', 'name' => 'Moderate sponsored ads'],
            ['slug' => 'platform.ads_manager_leads', 'group' => 'platform', 'name' => 'Import Ads Manager leads'],
            // Job Portal module
            ['slug' => 'portal.dashboard.view', 'group' => 'portal', 'name' => 'View job portal dashboard'],
            ['slug' => 'portal.companies.view', 'group' => 'portal', 'name' => 'View companies'],
            ['slug' => 'portal.companies.create', 'group' => 'portal', 'name' => 'Create company'],
            ['slug' => 'portal.companies.edit', 'group' => 'portal', 'name' => 'Edit company'],
            ['slug' => 'portal.companies.delete', 'group' => 'portal', 'name' => 'Delete company'],
            ['slug' => 'portal.jobs.view', 'group' => 'portal', 'name' => 'View jobs'],
            ['slug' => 'portal.jobs.create', 'group' => 'portal', 'name' => 'Create jobs'],
            ['slug' => 'portal.jobs.edit', 'group' => 'portal', 'name' => 'Edit jobs'],
            ['slug' => 'portal.jobs.delete', 'group' => 'portal', 'name' => 'Delete jobs'],
            ['slug' => 'portal.candidates.view', 'group' => 'portal', 'name' => 'View candidates'],
            ['slug' => 'portal.candidates.profile', 'group' => 'portal', 'name' => 'View candidate profile'],
            ['slug' => 'portal.applications.view', 'group' => 'portal', 'name' => 'View applications'],
            ['slug' => 'portal.applications.create', 'group' => 'portal', 'name' => 'Apply on behalf of candidate'],
            ['slug' => 'portal.applications.update_status', 'group' => 'portal', 'name' => 'Update application status'],
            ['slug' => 'portal.recruiter_assignments.manage', 'group' => 'portal', 'name' => 'Manage recruiter company assignments'],
            ['slug' => 'portal.recruiter_activity.view', 'group' => 'portal', 'name' => 'View recruiter apply activity'],
            ['slug' => 'portal.reports.view', 'group' => 'portal', 'name' => 'View reports'],
            ['slug' => 'portal.reports.export', 'group' => 'portal', 'name' => 'Export reports'],
            ['slug' => 'portal.users.view', 'group' => 'portal', 'name' => 'View portal users'],
            ['slug' => 'portal.users.create', 'group' => 'portal', 'name' => 'Create portal users'],
            ['slug' => 'portal.users.edit', 'group' => 'portal', 'name' => 'Edit portal users'],
            ['slug' => 'portal.users.delete', 'group' => 'portal', 'name' => 'Delete portal users'],
            ['slug' => 'portal.roles.view', 'group' => 'portal', 'name' => 'View roles'],
            ['slug' => 'portal.roles.create', 'group' => 'portal', 'name' => 'Create roles'],
            ['slug' => 'portal.roles.edit', 'group' => 'portal', 'name' => 'Edit roles'],
            ['slug' => 'portal.roles.delete', 'group' => 'portal', 'name' => 'Delete roles'],
            ['slug' => 'portal.settings.manage', 'group' => 'portal', 'name' => 'Manage portal settings'],
        ];
    }

    /** @return array<string, list<string>> */
    public static function rolePermissionMap(): array
    {
        $all = array_column(self::all(), 'slug');

        $portalAdmin = [
            'portal.dashboard.view',
            'portal.companies.view', 'portal.companies.edit',
            'portal.jobs.view', 'portal.jobs.edit',
            'portal.candidates.view', 'portal.candidates.profile',
            'portal.applications.view', 'portal.applications.create', 'portal.applications.update_status',
            'portal.recruiter_assignments.manage', 'portal.recruiter_activity.view',
            'portal.reports.view', 'portal.reports.export',
            'portal.users.view', 'portal.users.edit',
            'platform.users', 'platform.employers', 'platform.jobs', 'applications.view',
            'analytics.view', 'analytics.export',
        ];

        $recruiter = [
            'portal.companies.view',
            'portal.jobs.view', 'portal.jobs.edit',
            'portal.candidates.view', 'portal.candidates.profile',
            'portal.applications.view', 'portal.applications.create', 'portal.applications.update_status',
        ];

        $recruiterManager = [
            'portal.recruiter_assignments.manage',
            'portal.recruiter_activity.view',
            'portal.companies.view',
            'portal.jobs.view',
            'portal.applications.view',
            'portal.candidates.profile',
        ];

        return [
            'super_admin' => $all,
            'admin' => array_values(array_unique(array_merge(
                array_filter($all, fn (string $s) => ! in_array($s, ['rbac.manage_permissions', 'audit.view'], true)),
                $portalAdmin,
            ))),
            'recruiter' => $recruiter,
            'recruiter_manager' => $recruiterManager,
            'marketing' => [
                'leads.view', 'leads.view_all', 'leads.create', 'leads.import', 'leads.export',
                'leads.assign_manager', 'leads.reassign', 'leads.release', 'consultations.view',
                'analytics.view', 'applications.view', 'employer_payments.view',
                'platform.sponsored_ads', 'platform.ads_manager_leads',
            ],
            'asm' => [
                'leads.view', 'leads.assign_employee', 'leads.reassign', 'leads.update_stage',
                'leads.update_sales_status', 'leads.log_call', 'leads.manage_followups',
                'leads.take_back', 'kanban.view', 'staff.view', 'staff.manage', 'analytics.view',
                'applications.view', 'employer_payments.view',
            ],
            'sales_manager' => [
                'leads.view', 'leads.assign_employee', 'leads.reassign', 'leads.update_stage',
                'leads.update_sales_status', 'leads.log_call', 'leads.manage_followups',
                'leads.take_back', 'kanban.view', 'staff.view', 'staff.manage', 'analytics.view',
                'applications.view', 'employer_payments.view',
            ],
            'sales_employee' => [
                'leads.view', 'leads.update_stage', 'leads.update_sales_status',
                'leads.log_call', 'leads.manage_followups', 'kanban.view', 'analytics.view',
                'applications.view', 'employer_payments.view',
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
            'asm' => 'asm',
            'sales_manager' => 'sales_manager',
            'sales_employee' => 'sales_employee',
            'recruiter' => 'recruiter',
            'recruiter_manager' => 'recruiter_manager',
        ];
    }
}
