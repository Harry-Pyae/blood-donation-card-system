<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonationCentre extends Model
{
    protected $fillable = [
        'code', 'name', 'region', 'township', 'address', 'phone',
        'opening_hours', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }
}
