<?php

namespace App\Enums;

enum TenantType: string
{
    case Individual = 'individual';
    case Organization = 'organization';
    case Family = 'family';
}
