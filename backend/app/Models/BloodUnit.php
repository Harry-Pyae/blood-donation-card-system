<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BloodUnit extends Model
{
    protected $fillable = [
        'unit_number', 'trace_token', 'donation_id', 'parent_blood_unit_id', 'blood_group', 'component_type',
        'leukoreduced', 'irradiated', 'washed', 'collected_at', 'expires_at', 'storage_location',
        'status', 'released_at', 'released_by', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (BloodUnit $unit): void {
            if (! $unit->trace_token) {
                $unit->trace_token = Str::uuid()->toString();
            }
        });
    }

    public static function generateUnitNumber(): string
    {
        do {
            $number = 'BU-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (static::where('unit_number', $number)->exists());

        return $number;
    }

    protected function casts(): array
    {
        return [
            'collected_at' => 'date', 'expires_at' => 'date', 'released_at' => 'datetime',
            'leukoreduced' => 'boolean', 'irradiated' => 'boolean', 'washed' => 'boolean',
        ];
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_blood_unit_id'); }
    public function components(): HasMany { return $this->hasMany(self::class, 'parent_blood_unit_id'); }
    public function allocations(): HasMany { return $this->hasMany(BloodAllocation::class); }
}
