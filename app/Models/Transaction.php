<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'cylinder_id',
        'user_id',
        'transaction_type',
        'from_location_id',
        'to_location_id',
        'condition',
        'remaining_gas',
        'destination',
        'notes',
        'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'remaining_gas' => 'decimal:2',
            'transaction_date' => 'datetime',
            'transaction_type' => TransactionType::class,
        ];
    }

    public function cylinder(): BelongsTo
    {
        return $this->belongsTo(Cylinder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }
}
