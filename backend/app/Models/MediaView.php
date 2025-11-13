<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaView extends Model
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
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the media file that was viewed.
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    /**
     * Get the user that viewed the media file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}