<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdverseReaction extends Model
{
    public const STATUSES = ['reported', 'under_review', 'investigating', 'closed'];
    public const REACTION_TYPES = [
        'acute_hemolytic', 'delayed_hemolytic', 'febrile_non_hemolytic', 'allergic', 'anaphylactic',
        'taco', 'trali', 'septic', 'other',
    ];
    public const IMPUTABILITY = ['not_assessed', 'excluded', 'unlikely', 'possible', 'probable', 'definite'];
    public const OUTCOMES = ['recovered', 'ongoing', 'sequelae', 'death', 'unknown'];

    protected $fillable = [
        'reference', 'blood_allocation_id', 'hospital_id', 'reported_by', 'severity', 'symptoms',
        'action_taken', 'occurred_at', 'status', 'suspected_reaction_type', 'reaction_type',
        'imputability', 'outcome', 'investigation_notes', 'corrective_action', 'reviewed_by',
        'reviewed_at', 'closed_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at'=>'datetime', 'reviewed_at'=>'datetime', 'closed_at'=>'datetime'];
    }

    public static function generateReference(): string { return 'HVR-'.now()->format('ymd').'-'.strtoupper(Str::random(5)); }
    public function allocation(): BelongsTo { return $this->belongsTo(BloodAllocation::class, 'blood_allocation_id'); }
    public function hospital(): BelongsTo { return $this->belongsTo(Hospital::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function closedBy(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }
}
