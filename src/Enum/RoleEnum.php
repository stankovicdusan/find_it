<?php

namespace App\Enum;

enum RoleEnum: int
{
    case ADMIN = 1;
    case MANAGER = 2;
    case EMPLOYEE = 3;
}
