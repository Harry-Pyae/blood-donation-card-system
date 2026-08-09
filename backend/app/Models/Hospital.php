<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Hospital extends Model
{
    public const REGION_PREFIXES = [
        'Ayeyarwady Region' => 'AYE',
        'Bago Region' => 'BGO',
        'Magway Region' => 'MGW',
        'Mandalay Region' => 'MDY',
        'Sagaing Region' => 'SGG',
        'Tanintharyi Region' => 'TNI',
        'Yangon Region' => 'YGN',
    ];

    protected $fillable = ['code', 'name', 'region', 'address', 'phone', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public static function generateCode(string $region): string
    {
        $prefix = self::REGION_PREFIXES[$region]
            ?? throw new \InvalidArgumentException('Unsupported hospital region.');

        do {
            $code = $prefix.'-HSP-'.Str::upper(Str::random(6));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function bloodRequests(): HasMany { return $this->hasMany(BloodRequest::class); }
}
