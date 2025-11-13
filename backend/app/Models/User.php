<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    /**
     * Attributes to append to the model's array / JSON form.
     * We append `encrypted_name` so API responses include the 10-char value.
     */
    protected $fillable = [
        'name',
        'username_code',
        'email',
        'password',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function mediaFiles(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function storage(): HasOne
    {
        return $this->hasOne(UserStorage::class);
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withPivot('accepted_at')
            ->withTimestamps();
    }

    public function sentFriendRequests(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'pending')
            ->withTimestamps();
    }

    public function receivedFriendRequests(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
            ->wherePivot('status', 'pending')
            ->withTimestamps();
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'blocked')
            ->withTimestamps();
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $appKey = env('APP_KEY', '');

            $raw = hash('sha256', ($user->name ?? '') . $appKey . $user->id, true);
            $b64 = rtrim(strtr(base64_encode($raw), '+/', 'AZ'), '=');
            $clean = preg_replace('/[^A-Za-z0-9]/', 'A', $b64);
            $code = substr($clean, 0, 10);

            // Check for collisions with other users
            $exists = DB::table('users')->where('username_code', $code)->where('id', '<>', $user->id)->exists();
            if ($exists) {
                $fallback = substr(preg_replace('/[^A-Za-z0-9]/', 'A', base64_encode(hash('sha256', $user->id . $appKey, true))), 0, 10);
                $code = $fallback;
            }

            if (empty($user->username_code) || $user->username_code !== $code) {
                $user->username_code = $code;
                $user->saveQuietly();
            }

            // $from = '<fromaddress@gmail.com>';
            // $to = '<toaddress@yahoo.com>';
            // $subject = 'Hi!';
            // $body = "Hi,\n\nHow are you?";

            // $headers = array(
            //     'From' => $from,
            //     'To' => $to,
            //     'Subject' => $subject
            // );
            // $smtp = Mail::factory('smtp', array(
            //         'host' => 'ssl://smtp.gmail.com',
            //         'port' => '465',
            //         'auth' => true,
            //         'username' => 'johndoe@gmail.com',
            //         'password' => 'passwordxxx'
            //     ));

            // $mail = $smtp->send($to, $headers, $body);
            // Mail::sendNow()
            // Mail::to($to)->send(new welcome ($user));
            // mail()
            // // test check mail 
            // $to      = env('MAIL_FROM_ADDRESS');
            // $subject = 'the subject';
            // $message = 'your email  ';
            // $headers = 'From: webmaster@example.com'       . "\r\n" .
            //     'Reply-To: webmaster@example.com' . "\r\n" .
            //     'X-Mailer: PHP/' . phpversion();

            // mail($to, $subject, $message, $headers);
            $user->storage()->create([
                'used_storage' => 0,
                'max_storage' => 10 * 1024 * 1024 * 1024, // 10GB default
                'file_count' => 0,
            ]);
        });
    }
}
