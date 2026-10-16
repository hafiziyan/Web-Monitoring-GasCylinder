<?php

namespace App\Models;

use App\Enums\CylinderCondition;
use App\Enums\CylinderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cylinder extends Model
{
    use HasFactory;

    protected $fillable = [
        'cylinder_code',
        'serial_number',
        'gas_type_id',
        'supplier_id',
        'capacity',
        'unit',
        'status',
        'condition',
        'current_location_id',
        'qr_token',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
            'status' => CylinderStatus::class,
            'condition' => CylinderCondition::class,
        ];
    }

    public function gasType(): BelongsTo
    {
        return $this->belongsTo(GasType::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
