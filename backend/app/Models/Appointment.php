<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'donor_id', 'donation_centre_id', 'centre_name',
        'appointment_date', 'appointment_time', 'requested_region',
        'requested_township', 'purpose', 'source', 'status', 'notes',
        'acknowledged_at', 'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'acknowledged_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'APT-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(DonationCentre::class, 'donation_centre_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function donation(): HasOne
    {
        return $this->hasOne(Donation::class);
    }

    public function screenings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DonorScreening::class);
    }
}
