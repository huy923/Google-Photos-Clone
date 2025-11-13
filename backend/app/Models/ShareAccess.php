<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareAccess extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'share_id',
        'user_id',
        'email',
        'permission',
        'accessed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    /**
     * Get the share that owns the access record.
     */
    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    /**
     * Get the user that has access.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark as accessed.
     */
    public function markAsAccessed(): void
    {
        $this->update(['accessed_at' => now()]);
    }

    /**
     * Check if access has been used.
     */
    public function hasBeenAccessed(): bool
    {
        return !is_null($this->accessed_at);
    }
}