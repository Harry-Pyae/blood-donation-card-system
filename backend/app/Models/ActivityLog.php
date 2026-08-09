<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected $fillable = [
        'reference', 'type', 'action', 'subject_type', 'subject_id',
        'donor_id', 'user_id', 'result', 'details', 'source', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public static function record(array $attributes): self
    {
        $attributes['reference'] ??= 'HIS-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));

        return static::create($attributes);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
