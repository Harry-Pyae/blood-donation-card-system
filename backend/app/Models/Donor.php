<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Donor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference', 'user_id', 'full_name', 'date_of_birth', 'gender',
        'identity_document_type', 'identity_number', 'nrc_state',
        'nrc_township', 'nrc_type', 'nrc_serial', 'passport_number',
        'phone', 'phone_normalized', 'email', 'address', 'blood_group',
        'donation_type_preference',
        'emergency_contact', 'previous_donation', 'health_notes', 'consent_at',
        'status', 'eligibility_status', 'deferral_type', 'deferral_reason',
        'deferral_end_date', 'last_donation_date',
        'next_eligible_date', 'staff_notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'previous_donation' => 'boolean',
            'consent_at' => 'datetime',
            'last_donation_date' => 'date',
            'next_eligible_date' => 'date',
            'deferral_end_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function normalizePhone(?string $phone): string
    {
        return preg_replace('/[^0-9]+/', '', strtr((string) $phone, [
            '၀' => '0', '၁' => '1', '၂' => '2', '၃' => '3', '၄' => '4',
            '၅' => '5', '၆' => '6', '၇' => '7', '၈' => '8', '၉' => '9',
        ])) ?? '';
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'BC-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(DonorScreening::class);
    }

    public function latestScreening(): HasOne
    {
        return $this->hasOne(DonorScreening::class)->latestOfMany('screened_at');
    }

    public function card(): HasOne
    {
        return $this->hasOne(DonationCard::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
