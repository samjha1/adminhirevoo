<?php

namespace App\Enums;

enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Marketing = 'marketing';
    case SalesManager = 'sales_manager';
    case SalesEmployee = 'sales_employee';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Marketing => 'Marketing',
            self::SalesManager => 'Sales Manager',
            self::SalesEmployee => 'Sales Employee',
        };
    }

    public function crmRoleSlug(): string
    {
        return $this->value;
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isPlatformAdmin(): bool
    {
        return $this === self::SuperAdmin || $this === self::Admin;
    }

    public function hasUnrestrictedLeadVisibility(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::Marketing => true,
            default => false,
        };
    }

    /** @return list<self> */
    public static function assignableForMarketing(): array
    {
        return [self::SalesManager];
    }

    /** @return list<self> */
    public static function assignableForSalesManager(): array
    {
        return [self::SalesEmployee];
    }
}
