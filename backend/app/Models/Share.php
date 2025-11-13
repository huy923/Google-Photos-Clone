<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Share extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'share_token',
        'shareable_type',
        'shareable_id',
        'permission',
        'access_type',
        'expires_at',
        'view_count',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function shareAccess(): HasMany
    {
        return $this->hasMany(ShareAccess::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($share) {
            if (empty($share->share_token)) {
                $share->share_token = Str::random(32);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopePublic($query)
    {
        return $query->where('access_type', 'public');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function getUrlAttribute(): string
    {
        return route('shares.show', $this->share_token);
    }
}