<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LabTest extends Model
{
    protected $fillable = [
        'reference', 'donation_id', 'hiv_status', 'hepatitis_b_status', 'hepatitis_c_status', 'syphilis_status',
        'confirmed_blood_group', 'rhd_type', 'antibody_screen_status', 'htlv_status', 'malaria_status',
        'chagas_status', 'west_nile_status', 'zika_status', 'release_status', 'notes', 'tested_by', 'tested_at',
        'released_at', 'released_by',
    ];
    protected function casts(): array { return ['tested_at' => 'datetime', 'released_at' => 'datetime']; }
    public static function generateReference(): string { return 'LAB-'.now()->format('ymd').'-'.strtoupper(Str::random(5)); }
    public function donation(): BelongsTo { return $this->belongsTo(Donation::class); }
    public function testedBy(): BelongsTo { return $this->belongsTo(User::class, 'tested_by'); }
    public function releasedBy(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }
}
