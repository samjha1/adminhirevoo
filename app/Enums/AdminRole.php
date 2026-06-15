<?php

namespace App\Enums;

enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Marketing = 'marketing';
    case Asm = 'asm';
    case SalesManager = 'sales_manager';
    case SalesEmployee = 'sales_employee';
    case Recruiter = 'recruiter';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Marketing => 'Marketing',
            self::Asm => 'ASM (Area Sales Manager)',
            self::SalesManager => 'Sales Manager',
            self::SalesEmployee => 'Sales Employee',
            self::Recruiter => 'Recruiter',
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

    public function isSalesFieldRole(): bool
    {
        return match ($this) {
            self::Asm, self::SalesManager, self::SalesEmployee => true,
            default => false,
        };
    }

    public function hasUnrestrictedLeadVisibility(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::Marketing => true,
            default => false,
        };
    }

    public function hasSubtreeLeadVisibility(): bool
    {
        return $this === self::Asm;
    }

    public function requiredManagerRole(): ?self
    {
        return match ($this) {
            self::Asm => self::Admin,
            self::SalesManager => self::Asm,
            self::SalesEmployee => self::SalesManager,
            default => null,
        };
    }

    /** @return list<self> */
    public function assignableChildRoles(): array
    {
        return match ($this) {
            self::Asm => [self::SalesManager],
            self::SalesManager => [self::SalesEmployee],
            default => [],
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

    /** @return list<self> */
    public static function assignableForAsm(): array
    {
        return [self::SalesManager];
    }
}
