<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodAllocation extends Model
{
    protected $fillable = ['blood_request_id', 'blood_unit_id', 'crossmatch_result', 'status', 'allocated_by', 'allocated_at', 'dispatched_at', 'received_at', 'transfused_at', 'notes'];
    protected function casts(): array { return ['allocated_at'=>'datetime','dispatched_at'=>'datetime','received_at'=>'datetime','transfused_at'=>'datetime']; }
    public function request(): BelongsTo { return $this->belongsTo(BloodRequest::class, 'blood_request_id'); }
    public function unit(): BelongsTo { return $this->belongsTo(BloodUnit::class, 'blood_unit_id'); }
    public function reactions(): HasMany { return $this->hasMany(AdverseReaction::class); }
}
