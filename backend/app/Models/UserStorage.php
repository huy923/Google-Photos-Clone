<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStorage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_storage';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'used_storage',
        'max_storage',
        'file_count',
    ];

    /**
     * Get the user that owns the storage.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the available storage in bytes.
     */
    public function getAvailableStorageAttribute(): int
    {
        return max(0, $this->max_storage - $this->used_storage);
    }

    /**
     * Get the storage usage percentage.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->max_storage == 0) {
            return 0;
        }
        
        return ($this->used_storage / $this->max_storage) * 100;
    }

    /**
     * Get the used storage in human readable format.
     */
    public function getUsedStorageHumanAttribute(): string
    {
        return $this->formatBytes($this->used_storage);
    }

    /**
     * Get the max storage in human readable format.
     */
    public function getMaxStorageHumanAttribute(): string
    {
        return $this->formatBytes($this->max_storage);
    }

    /**
     * Get the available storage in human readable format.
     */
    public function getAvailableStorageHumanAttribute(): string
    {
        return $this->formatBytes($this->available_storage);
    }

    /**
     * Check if storage is full.
     */
    public function isFull(): bool
    {
        return $this->used_storage >= $this->max_storage;
    }

    /**
     * Check if there's enough storage for a file.
     */
    public function hasEnoughStorage(int $fileSize): bool
    {
        return ($this->used_storage + $fileSize) <= $this->max_storage;
    }

    /**
     * Add storage usage.
     */
    public function addStorage(int $bytes): void
    {
        $this->increment('used_storage', $bytes);
        $this->increment('file_count');
    }

    /**
     * Remove storage usage.
     */
    public function removeStorage(int $bytes): void
    {
        $this->decrement('used_storage', $bytes);
        $this->decrement('file_count');
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}