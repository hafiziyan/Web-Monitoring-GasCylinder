<?php

namespace App\Enums;

enum TransactionType: string
{
    case In = 'IN';
    case Out = 'OUT';
    case Return = 'RETURN';
    case Transfer = 'TRANSFER';
    case Maintenance = 'MAINTENANCE';
    case UseStart = 'USE_START';
    case UseEnd = 'USE_END';
}
