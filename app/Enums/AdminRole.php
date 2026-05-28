<?php

namespace App\Enums;

enum AdminRole: string
{
    case Admin = 'admin';
    case Marketing = 'marketing';
    case SalesManager = 'sales_manager';
    case SalesEmployee = 'sales_employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Marketing => 'Marketing',
            self::SalesManager => 'Sales Manager',
            self::SalesEmployee => 'Sales Employee',
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
