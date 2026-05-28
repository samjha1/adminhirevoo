<?php

namespace App\Policies;

use App\Enums\AdminRole;
use App\Models\Admin;

class AdminStaffPolicy
{
    public function manage(Admin $admin): bool
    {
        return $admin->role === AdminRole::Admin;
    }
}
