<?php

namespace App\Support;

use App\Models\Admin;

final class PortalApplyPermission
{
    public static function canApplyOnBehalf(?Admin $admin): bool
    {
        if ($admin === null) {
            return false;
        }

        foreach ([
            'portal.applications.create',
            'portal.applications.update_status',
            'portal.jobs.edit',
            'platform.jobs',
            'applications.view',
        ] as $slug) {
            if ($admin->canPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    public static function jobAcceptsRecruiterApply(string $status): bool
    {
        return in_array($status, ['active', 'draft'], true);
    }
}
