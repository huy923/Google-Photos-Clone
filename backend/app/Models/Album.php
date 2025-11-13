<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'cover_photo_path',
        'type',
        'auto_criteria',
        'is_public',
        'is_deleted',
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auto_criteria' => 'array',
            'is_public' => 'boolean',
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the album.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the media files in this album.
     */
    public function mediaFiles(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class, 'album_media')
            ->withPivot('sort_order')
            ->orderBy('album_media.sort_order')
            ->withTimestamps();
    }

    /**
     * Get the shares for this album.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(Share::class, 'shareable_id')
            ->where('shareable_type', 'album');
    }

    /**
     * Scope a query to only include non-deleted albums.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope a query to only include public albums.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include manual albums.
     */
    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }

    /**
     * Scope a query to only include auto albums.
     */
    public function scopeAuto($query)
    {
        return $query->where('type', '!=', 'manual');
    }

    /**
     * Get the cover photo URL.
     */
    public function getCoverPhotoUrlAttribute(): string
    {
        return $this->cover_photo_path ? asset('storage/' . $this->cover_photo_path) : '';
    }

    /**
     * Get the media count.
     */
    public function getMediaCountAttribute(): int
    {
        return $this->mediaFiles()->count();
    }
}