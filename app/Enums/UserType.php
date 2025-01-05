<?php

namespace App\Enums;
enum UserType: string
{
    case SuperAdmin = 'super_admin';
    case Manager = 'manager';
    case Employee = 'employee';
}
