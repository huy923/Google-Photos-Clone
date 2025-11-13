<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaMetadata extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'media_file_id',
        'taken_at',
        'camera_make',
        'camera_model',
        'lens_model',
        'latitude',
        'longitude',
        'location_name',
        'city',
        'country',
        'altitude',
        'focal_length',
        'aperture',
        'shutter_speed',
        'iso',
        'flash',
        'white_balance',
        'exif_data',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'exif_data' => 'array',
        ];
    }

    /**
     * Get the media file that owns the metadata.
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    /**
     * Get the formatted location string.
     */
    public function getFormattedLocationAttribute(): string
    {
        $parts = array_filter([
            $this->city,
            $this->country,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get the formatted camera info.
     */
    public function getFormattedCameraAttribute(): string
    {
        $parts = array_filter([
            $this->camera_make,
            $this->camera_model,
        ]);
        
        return implode(' ', $parts);
    }

    /**
     * Get the formatted lens info.
     */
    public function getFormattedLensAttribute(): string
    {
        return $this->lens_model ?? '';
    }
}