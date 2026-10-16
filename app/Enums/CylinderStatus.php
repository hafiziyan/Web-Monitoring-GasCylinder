<?php

namespace App\Enums;

enum CylinderStatus: string
{
    case Available = 'AVAILABLE';
    case InUse = 'IN_USE';
    case Empty = 'EMPTY';
    case Maintenance = 'MAINTENANCE';
    case Outside = 'OUTSIDE';
    case Inactive = 'INACTIVE';
}
