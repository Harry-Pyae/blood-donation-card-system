<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BloodRequest extends Model
{
    protected $fillable = [
        'reference', 'hospital_id', 'requested_by', 'patient_reference', 'blood_group', 'component_type',
        'requires_leukoreduced', 'requires_irradiated', 'requires_washed', 'quantity', 'priority', 'status',
        'clinical_note', 'decision_note', 'reviewed_by', 'reviewed_at',
    ];
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'requires_leukoreduced' => 'boolean',
            'requires_irradiated' => 'boolean',
            'requires_washed' => 'boolean',
        ];
    }
    public static function generateReference(): string { return 'REQ-'.now()->format('ymd').'-'.strtoupper(Str::random(5)); }
    public function hospital(): BelongsTo { return $this->belongsTo(Hospital::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function allocations(): HasMany { return $this->hasMany(BloodAllocation::class); }
}
