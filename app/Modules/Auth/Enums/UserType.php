<?php

namespace App\Modules\Auth\Enums;

enum UserType: string
{
    case ADMIN    = 'admin';
    case TENANT   = 'tenant';
    case EMPLOYEE = 'employee';
    case CUSTOMER = 'customer';
}
