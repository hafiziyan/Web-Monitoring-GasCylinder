<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function cylinders(): HasMany
    {
        return $this->hasMany(Cylinder::class, 'current_location_id');
    }

    public function fromTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_location_id');
    }

    public function toTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_location_id');
    }
}
