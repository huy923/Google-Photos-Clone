<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'original_name',
        'filename',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'file_type',
        'file_size',
        'width',
        'height',
        'duration',
        'is_processed',
        'is_optimized',
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
            'is_processed' => 'boolean',
            'is_optimized' => 'boolean',
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the media file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the media file's metadata.
     */
    public function metadata(): HasOne
    {
        return $this->hasOne(MediaMetadata::class);
    }

    /**
     * Get the albums that contain this media file.
     */
    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_media')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Get the tags associated with this media file.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class, 'media_file_tags');
    }

    /**
     * Get the comments for this media file.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(MediaComment::class);
    }

    /**
     * Get the favorites for this media file.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(MediaFavorite::class);
    }

    /**
     * Get the views for this media file.
     */
    public function views(): HasMany
    {
        return $this->hasMany(MediaView::class);
    }

    /**
     * Get the shares for this media file.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(Share::class, 'shareable_id')
            ->where('shareable_type', 'media_file');
    }

    /**
     * Scope a query to only include non-deleted media files.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope a query to only include processed media files.
     */
    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    /**
     * Scope a query to only include images.
     */
    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }

    /**
     * Scope a query to only include videos.
     */
    public function scopeVideos($query)
    {
        return $query->where('file_type', 'video');
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the file URL.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get the thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : $this->url;
    }
}