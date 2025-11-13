<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaComment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'media_file_id',
        'user_id',
        'comment',
        'parent_id',
        'is_edited',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
        ];
    }

    /**
     * Get the media file that owns the comment.
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    /**
     * Get the user that wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaComment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(MediaComment::class, 'parent_id');
    }

    /**
     * Scope a query to only include top-level comments.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include replies.
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }
}