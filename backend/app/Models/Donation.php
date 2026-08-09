<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Donation extends Model
{
    protected $fillable = [
        'reference', 'donor_id', 'appointment_id', 'donation_centre_id',
        'donation_date', 'quantity_ml', 'blood_group', 'donation_type', 'screening_result',
        'status', 'bag_unit_number', 'expires_at', 'storage_location',
        'screening_notes', 'recorded_by',
    ];

    public static function generateReference(): string
    {
        do {
            $reference = 'DON-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    protected function casts(): array
    {
        return ['donation_date' => 'date', 'expires_at' => 'date'];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(DonationCentre::class, 'donation_centre_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function bloodUnit(): HasOne
    {
        return $this->hasOne(BloodUnit::class)->where('component_type', 'whole_blood');
    }

    public function bloodUnits(): HasMany { return $this->hasMany(BloodUnit::class); }
    public function labTest(): HasOne { return $this->hasOne(LabTest::class); }

    public function screening(): HasOne
    {
        return $this->hasOne(DonorScreening::class);
    }
}
