<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DonationCard extends Model
{
    protected $fillable = [
        'card_number', 'donor_id', 'issued_at', 'expires_at', 'status',
        'qr_token', 'replacement_count', 'print_count', 'issued_by', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (DonationCard $card): void {
            if (! $card->qr_token) {
                $card->qr_token = Str::uuid()->toString();
            }
        });
    }

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date'];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
