<?php

namespace App\Enums;

enum CylinderCondition: string
{
    case Good = 'GOOD';
    case Damaged = 'DAMAGED';
    case Maintenance = 'MAINTENANCE';
}
