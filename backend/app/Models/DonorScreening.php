<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DonorScreening extends Model
{
    protected $fillable = [
        'reference', 'donor_id', 'appointment_id', 'donation_id',
        'donation_centre_id', 'screened_at', 'next_screening_date',
        'weight_kg', 'hemoglobin_level', 'systolic_blood_pressure',
        'diastolic_blood_pressure', 'pulse_rate', 'body_temperature_celsius',
        'medication_flag', 'current_medications', 'recent_travel_flag',
        'recent_travel_details', 'high_risk_activity_flag',
        'high_risk_activity_details', 'outcome', 'deferral_type',
        'deferral_reason', 'deferral_end_date', 'notes',
        'verified_by_staff_id',
    ];

    protected function casts(): array
    {
        return [
            'screened_at' => 'datetime',
            'next_screening_date' => 'date',
            'weight_kg' => 'decimal:2',
            'hemoglobin_level' => 'decimal:2',
            'body_temperature_celsius' => 'decimal:2',
            'medication_flag' => 'boolean',
            'recent_travel_flag' => 'boolean',
            'high_risk_activity_flag' => 'boolean',
            'deferral_end_date' => 'date',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'SCR-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(DonationCentre::class, 'donation_centre_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_staff_id');
    }
}
