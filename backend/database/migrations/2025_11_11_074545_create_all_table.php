<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username_code', 10)->nullable()->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();
        });

        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'blocked'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'friend_id']);
            $table->index('status');
        });

        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_name');
            $table->string('filename');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('mime_type');
            $table->enum('file_type', ['image', 'video', 'gif']);
            $table->bigInteger('file_size'); // in bytes
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable(); // for videos, in seconds
            $table->boolean('is_processed')->default(false);
            $table->boolean('is_optimized')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'file_type']);
            $table->index(['user_id', 'is_deleted']);
            $table->index('created_at');
        });

        Schema::create('media_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->onDelete('cascade');
            $table->timestamp('taken_at')->nullable();
            $table->string('camera_make')->nullable();
            $table->string('camera_model')->nullable();
            $table->string('lens_model')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->integer('altitude')->nullable();
            $table->decimal('focal_length', 8, 2)->nullable();
            $table->decimal('aperture', 4, 2)->nullable();
            $table->decimal('shutter_speed', 8, 2)->nullable();
            $table->integer('iso')->nullable();
            $table->string('flash')->nullable();
            $table->string('white_balance')->nullable();
            $table->json('exif_data')->nullable();
            $table->timestamps();
            
            $table->index('taken_at');
            $table->index(['latitude', 'longitude']);
            $table->index(['city', 'country']);
        });

        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cover_photo_path')->nullable();
            $table->enum('type', ['manual', 'auto_date', 'auto_location', 'auto_face'])->default('manual');
            $table->json('auto_criteria')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_deleted']);
            $table->index('type');
        });

        Schema::create('album_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->onDelete('cascade');
            $table->foreignId('media_file_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['album_id', 'media_file_id']);
            $table->index('sort_order');
        });

        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('share_token')->unique();
            $table->enum('shareable_type', ['media_file', 'album']);
            $table->unsignedBigInteger('shareable_id');
            $table->enum('permission', ['view', 'download', 'comment'])->default('view');
            $table->enum('access_type', ['public', 'friends', 'specific'])->default('public');
            $table->timestamp('expires_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['shareable_type', 'shareable_id']);
            $table->index('expires_at');
        });

        Schema::create('share_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email')->nullable();
            $table->enum('permission', ['view', 'download', 'comment'])->default('view');
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index('type');
        });

        Schema::create('user_storage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->bigInteger('used_storage')->default(0);
            $table->bigInteger('max_storage')->default(5368709120); // 5GB default
            $table->integer('file_count')->default(0);
            $table->timestamps();
            
            $table->unique('user_id');
        });

        Schema::create('media_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#007bff');
            $table->timestamps();
        });

        Schema::create('media_file_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('media_tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['media_file_id', 'media_tag_id']);
        });

        Schema::create('media_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('comment');
            $table->foreignId('parent_id')->nullable()->constrained('media_comments')->onDelete('cascade');
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            
            $table->index('parent_id');
        });

        Schema::create('media_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['media_file_id', 'user_id']);
        });

        Schema::create('media_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
            
            $table->index('viewed_at');
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        $this->insertSampleData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_views');
        Schema::dropIfExists('media_favorites');
        Schema::dropIfExists('media_comments');
        Schema::dropIfExists('media_file_tags');
        Schema::dropIfExists('media_tags');
        Schema::dropIfExists('user_storage');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('share_access');
        Schema::dropIfExists('shares');
        Schema::dropIfExists('album_media');
        Schema::dropIfExists('albums');
        Schema::dropIfExists('media_metadata');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('friendships');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('personal_access_tokens');
    }

    /**
     * Insert sample data
     */
    private function insertSampleData(): void
    {
        $users = [
            [
                'id' => 1,
                'name' => 'Nguyễn Văn An',
                'email' => 'an.nguyen@example.com',
                'password' => Hash::make('password123'),
                'avatar' => 'avatars/an_nguyen.jpg',
                'is_active' => true,
                'last_login_at' => now()->subDays(2),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(2),
            ],
            [
                'id' => 2,
                'name' => 'Trần Thị Bình',
                'email' => 'binh.tran@example.com',
                'password' => Hash::make('password123'),
                'avatar' => 'avatars/binh_tran.jpg',
                'is_active' => true,
                'last_login_at' => now()->subHours(5),
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subHours(5),
            ],
            [
                'id' => 3,
                'name' => 'Lê Minh Cường',
                'email' => 'cuong.le@example.com',
                'password' => Hash::make('password123'),
                'avatar' => 'avatars/cuong_le.jpg',
                'is_active' => true,
                'last_login_at' => now()->subDays(1),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(1),
            ],
            [
                'id' => 4,
                'name' => 'Phạm Thị Dung',
                'email' => 'dung.pham@example.com',
                'password' => Hash::make('password123'),
                'avatar' => 'avatars/dung_pham.jpg',
                'is_active' => true,
                'last_login_at' => now()->subMinutes(30),
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'id' => 5,
                'name' => 'Hoàng Văn Em',
                'email' => 'em.hoang@example.com',
                'password' => Hash::make('password123'),
                'avatar' => 'avatars/em_hoang.jpg',
                'is_active' => false,
                'last_login_at' => now()->subWeeks(2),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subWeeks(2),
            ],
            [
                'id' => 6,
                'name' => 'Nguyễn Văn F',
                'email' => 'test',
                'password' => Hash::make('test'),
                'avatar' => 'avatars/f_nguyen.jpg',
                'is_active' => true,
                'last_login_at' => now()->subDays(5),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
        ];

        DB::table('users')->insert($users);
        DB::table('users')->orderBy('id')->chunk(100, function ($users) {
            $appKey = env('APP_KEY', '');
            foreach ($users as $u) {
                $raw = hash('sha256', ($u->name ?? '') . $appKey . $u->id, true);
                $b64 = rtrim(strtr(base64_encode($raw), '+/', 'AZ'), '=');
                $clean = preg_replace('/[^A-Za-z0-9]/', 'A', $b64);
                $code = substr($clean, 0, 10);

                $exists = DB::table('users')->where('username_code', $code)->exists();
                if ($exists) {
                    $fallback = substr(preg_replace('/[^A-Za-z0-9]/', 'A', base64_encode(hash('sha256', $u->id . $appKey, true))), 0, 10);
                    $code = $fallback;
                }

                DB::table('users')->where('id', $u->id)->update(['username_code' => $code]);
            }
        });
        $profiles = [
            [
                'user_id' => 1,
                'first_name' => 'Văn An',
                'last_name' => 'Nguyễn',
                'bio' => 'Nhiếp ảnh gia chuyên nghiệp, yêu thích chụp ảnh phong cảnh và cuộc sống hàng ngày.',
                'phone' => '+84 123 456 789',
                'birth_date' => '1990-05-15',
                'location' => 'Hà Nội, Việt Nam',
                'website' => 'https://annguyen.photo',
                'preferences' => json_encode(['theme' => 'dark', 'language' => 'vi']),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(2),
            ],
            [
                'user_id' => 2,
                'first_name' => 'Thị Bình',
                'last_name' => 'Trần',
                'bio' => 'Du lịch và khám phá thế giới qua ống kính. Chia sẻ những khoảnh khắc đẹp nhất.',
                'phone' => '+84 987 654 321',
                'birth_date' => '1992-08-22',
                'location' => 'TP. Hồ Chí Minh, Việt Nam',
                'website' => 'https://binhtravel.com',
                'preferences' => json_encode(['theme' => 'light', 'language' => 'vi']),
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subHours(5),
            ],
            [
                'user_id' => 3,
                'first_name' => 'Minh Cường',
                'last_name' => 'Lê',
                'bio' => 'Lập trình viên và nhiếp ảnh gia nghiệp dư. Đam mê công nghệ và nghệ thuật.',
                'phone' => '+84 555 123 456',
                'birth_date' => '1988-12-10',
                'location' => 'Đà Nẵng, Việt Nam',
                'website' => 'https://cuongdev.com',
                'preferences' => json_encode(['theme' => 'dark', 'language' => 'en']),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(1),
            ],
            [
                'user_id' => 4,
                'first_name' => 'Thị Dung',
                'last_name' => 'Phạm',
                'bio' => 'Nghệ sĩ và nhà thiết kế. Tạo ra những tác phẩm nghệ thuật độc đáo.',
                'phone' => '+84 333 777 999',
                'birth_date' => '1995-03-18',
                'location' => 'Huế, Việt Nam',
                'website' => 'https://dungart.com',
                'preferences' => json_encode(['theme' => 'light', 'language' => 'vi']),
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'user_id' => 5,
                'first_name' => 'Văn Em',
                'last_name' => 'Hoàng',
                'bio' => 'Sinh viên ngành nhiếp ảnh. Học hỏi và phát triển kỹ năng chụp ảnh.',
                'phone' => '+84 111 222 333',
                'birth_date' => '2000-07-25',
                'location' => 'Cần Thơ, Việt Nam',
                'website' => null,
                'preferences' => json_encode(['theme' => 'light', 'language' => 'vi']),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subWeeks(2),
            ]
        ];

        DB::table('profiles')->insert($profiles);

        $userStorage = [
            [
                'user_id' => 1,
                'used_storage' => 2147483648, // 2GB
                'max_storage' => 10737418240, // 10GB
                'file_count' => 45,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(2),
            ],
            [
                'user_id' => 2,
                'used_storage' => 1610612736, // 1.5GB
                'max_storage' => 8589934592, // 8GB
                'file_count' => 38,
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subHours(5),
            ],
            [
                'user_id' => 3,
                'used_storage' => 1073741824, // 1GB
                'max_storage' => 5368709120, // 5GB
                'file_count' => 25,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(1),
            ],
            [
                'user_id' => 4,
                'used_storage' => 805306368, // 750MB
                'max_storage' => 5368709120, // 5GB
                'file_count' => 18,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'user_id' => 5,
                'used_storage' => 268435456, // 250MB
                'max_storage' => 5368709120, // 5GB
                'file_count' => 8,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subWeeks(2),
            ]
        ];

        DB::table('user_storage')->insert($userStorage);

        $mediaFiles = [
            [
                'id' => 1,
                'user_id' => 1,
                'original_name' => 'hoang_liem_sapa.jpg',
                'filename' => 'hoang_liem_sapa_20241201_001.jpg',
                'file_path' => 'media/images/hoang_liem_sapa_20241201_001.jpg',
                'thumbnail_path' => 'media/thumbnails/hoang_liem_sapa_20241201_001_thumb.jpg',
                'mime_type' => 'image/jpeg',
                'file_type' => 'image',
                'file_size' => 2048576, // 2MB
                'width' => 1920,
                'height' => 1080,
                'duration' => null,
                'is_processed' => true,
                'is_optimized' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-01 06:30:00',
                'updated_at' => '2024-12-01 06:30:00',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'original_name' => 'bien_nha_trang_sunset.jpg',
                'filename' => 'bien_nha_trang_sunset_20241202_002.jpg',
                'file_path' => 'media/images/bien_nha_trang_sunset_20241202_002.jpg',
                'thumbnail_path' => 'media/thumbnails/bien_nha_trang_sunset_20241202_002_thumb.jpg',
                'mime_type' => 'image/jpeg',
                'file_type' => 'image',
                'file_size' => 3145728, // 3MB
                'width' => 2560,
                'height' => 1440,
                'duration' => null,
                'is_processed' => true,
                'is_optimized' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-02 17:45:00',
                'updated_at' => '2024-12-02 17:45:00',
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'original_name' => 'pho_co_hoi_an.jpg',
                'filename' => 'pho_co_hoi_an_20241203_003.jpg',
                'file_path' => 'media/images/pho_co_hoi_an_20241203_003.jpg',
                'thumbnail_path' => 'media/thumbnails/pho_co_hoi_an_20241203_003_thumb.jpg',
                'mime_type' => 'image/jpeg',
                'file_type' => 'image',
                'file_size' => 1572864, // 1.5MB
                'width' => 1600,
                'height' => 1200,
                'duration' => null,
                'is_processed' => true,
                'is_optimized' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-03 14:20:00',
                'updated_at' => '2024-12-03 14:20:00',
            ],
            [
                'id' => 4,
                'user_id' => 1,
                'original_name' => 'timelapse_ho_chi_minh_city.mp4',
                'filename' => 'timelapse_ho_chi_minh_city_20241204_004.mp4',
                'file_path' => 'media/videos/timelapse_ho_chi_minh_city_20241204_004.mp4',
                'thumbnail_path' => 'media/thumbnails/timelapse_ho_chi_minh_city_20241204_004_thumb.jpg',
                'mime_type' => 'video/mp4',
                'file_type' => 'video',
                'file_size' => 52428800, // 50MB
                'width' => 1920,
                'height' => 1080,
                'duration' => 120, // 2 phút
                'is_processed' => true,
                'is_optimized' => false,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-04 18:00:00',
                'updated_at' => '2024-12-04 18:00:00',
            ],
            [
                'id' => 5,
                'user_id' => 2,
                'original_name' => 'street_food_hanoi.mp4',
                'filename' => 'street_food_hanoi_20241205_005.mp4',
                'file_path' => 'media/videos/street_food_hanoi_20241205_005.mp4',
                'thumbnail_path' => 'media/thumbnails/street_food_hanoi_20241205_005_thumb.jpg',
                'mime_type' => 'video/mp4',
                'file_type' => 'video',
                'file_size' => 31457280, // 30MB
                'width' => 1280,
                'height' => 720,
                'duration' => 180, // 3 phút
                'is_processed' => true,
                'is_optimized' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-05 19:30:00',
                'updated_at' => '2024-12-05 19:30:00',
            ],
            [
                'id' => 6,
                'user_id' => 4,
                'original_name' => 'cat_playing_animation.gif',
                'filename' => 'cat_playing_animation_20241206_006.gif',
                'file_path' => 'media/gifs/cat_playing_animation_20241206_006.gif',
                'thumbnail_path' => 'media/thumbnails/cat_playing_animation_20241206_006_thumb.jpg',
                'mime_type' => 'image/gif',
                'file_type' => 'gif',
                'file_size' => 2097152, // 2MB
                'width' => 480,
                'height' => 480,
                'duration' => 5, // 5 giây
                'is_processed' => true,
                'is_optimized' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-06 20:15:00',
                'updated_at' => '2024-12-06 20:15:00',
            ]
        ];

        DB::table('media_files')->insert($mediaFiles);

        $mediaMetadata = [
            [
                'media_file_id' => 1,
                'taken_at' => '2024-12-01 06:30:00',
                'camera_make' => 'Canon',
                'camera_model' => 'EOS R5',
                'lens_model' => 'RF 24-70mm f/2.8L IS USM',
                'latitude' => 22.3369,
                'longitude' => 103.8440,
                'location_name' => 'Sapa, Lào Cai',
                'city' => 'Sapa',
                'country' => 'Việt Nam',
                'altitude' => 1600,
                'focal_length' => 35.0,
                'aperture' => 8.0,
                'shutter_speed' => 0.008,
                'iso' => 100,
                'flash' => 'No Flash',
                'white_balance' => 'Auto',
                'exif_data' => json_encode(['exposure_mode' => 'Manual', 'metering_mode' => 'Center-weighted']),
                'created_at' => '2024-12-01 06:30:00',
                'updated_at' => '2024-12-01 06:30:00',
            ],
            [
                'media_file_id' => 2,
                'taken_at' => '2024-12-02 17:45:00',
                'camera_make' => 'Sony',
                'camera_model' => 'A7R IV',
                'lens_model' => 'FE 16-35mm f/2.8 GM',
                'latitude' => 12.2388,
                'longitude' => 109.1967,
                'location_name' => 'Nha Trang, Khánh Hòa',
                'city' => 'Nha Trang',
                'country' => 'Việt Nam',
                'altitude' => 5,
                'focal_length' => 24.0,
                'aperture' => 11.0,
                'shutter_speed' => 0.017,
                'iso' => 200,
                'flash' => 'No Flash',
                'white_balance' => 'Auto',
                'exif_data' => json_encode(['exposure_mode' => 'Aperture Priority', 'metering_mode' => 'Matrix']),
                'created_at' => '2024-12-02 17:45:00',
                'updated_at' => '2024-12-02 17:45:00',
            ],
            [
                'media_file_id' => 3,
                'taken_at' => '2024-12-03 14:20:00',
                'camera_make' => 'Fujifilm',
                'camera_model' => 'X-T4',
                'lens_model' => 'XF 23mm f/1.4 R',
                'latitude' => 15.8801,
                'longitude' => 108.3380,
                'location_name' => 'Hội An, Quảng Nam',
                'city' => 'Hội An',
                'country' => 'Việt Nam',
                'altitude' => 10,
                'focal_length' => 23.0,
                'aperture' => 2.8,
                'shutter_speed' => 0.004,
                'iso' => 400,
                'flash' => 'No Flash',
                'white_balance' => 'Auto',
                'exif_data' => json_encode(['exposure_mode' => 'Manual', 'metering_mode' => 'Spot']),
                'created_at' => '2024-12-03 14:20:00',
                'updated_at' => '2024-12-03 14:20:00',
            ],
            [
                'media_file_id' => 4,
                'taken_at' => '2024-12-04 18:00:00',
                'camera_make' => 'DJI',
                'camera_model' => 'Air 2S',
                'lens_model' => 'Built-in',
                'latitude' => 10.8231,
                'longitude' => 106.6297,
                'location_name' => 'TP. Hồ Chí Minh',
                'city' => 'TP. Hồ Chí Minh',
                'country' => 'Việt Nam',
                'altitude' => 50,
                'focal_length' => null,
                'aperture' => null,
                'shutter_speed' => null,
                'iso' => null,
                'flash' => null,
                'white_balance' => null,
                'exif_data' => json_encode(['resolution' => '4K', 'fps' => 30, 'bitrate' => '100 Mbps']),
                'created_at' => '2024-12-04 18:00:00',
                'updated_at' => '2024-12-04 18:00:00',
            ],
            [
                'media_file_id' => 5,
                'taken_at' => '2024-12-05 19:30:00',
                'camera_make' => 'Apple',
                'camera_model' => 'iPhone 15 Pro',
                'lens_model' => 'Built-in',
                'latitude' => 21.0285,
                'longitude' => 105.8542,
                'location_name' => 'Hà Nội',
                'city' => 'Hà Nội',
                'country' => 'Việt Nam',
                'altitude' => 20,
                'focal_length' => null,
                'aperture' => null,
                'shutter_speed' => null,
                'iso' => null,
                'flash' => null,
                'white_balance' => null,
                'exif_data' => json_encode(['resolution' => 'HD', 'fps' => 60, 'bitrate' => '50 Mbps']),
                'created_at' => '2024-12-05 19:30:00',
                'updated_at' => '2024-12-05 19:30:00',
            ],
            [
                'media_file_id' => 6,
                'taken_at' => '2024-12-06 20:15:00',
                'camera_make' => 'Apple',
                'camera_model' => 'iPhone 14',
                'lens_model' => 'Built-in',
                'latitude' => null,
                'longitude' => null,
                'location_name' => 'Nhà riêng, Hà Nội',
                'city' => 'Hà Nội',
                'country' => 'Việt Nam',
                'altitude' => null,
                'focal_length' => null,
                'aperture' => null,
                'shutter_speed' => null,
                'iso' => null,
                'flash' => null,
                'white_balance' => null,
                'exif_data' => json_encode(['format' => 'GIF', 'frames' => 150, 'fps' => 30]),
                'created_at' => '2024-12-06 20:15:00',
                'updated_at' => '2024-12-06 20:15:00',
            ]
        ];

        DB::table('media_metadata')->insert($mediaMetadata);

        $albums = [
            [
                'id' => 1,
                'user_id' => 1,
                'name' => 'Du lịch Sapa 2024',
                'description' => 'Những khoảnh khắc đẹp nhất trong chuyến du lịch Sapa tháng 12/2024',
                'cover_photo_path' => 'albums/sapa_2024_cover.jpg',
                'type' => 'manual',
                'auto_criteria' => null,
                'is_public' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-01 07:00:00',
                'updated_at' => '2024-12-01 07:00:00',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'name' => 'Ảnh phong cảnh biển',
                'description' => 'Tuyển tập ảnh phong cảnh biển đẹp nhất từ các chuyến đi',
                'cover_photo_path' => 'albums/sea_landscape_cover.jpg',
                'type' => 'manual',
                'auto_criteria' => null,
                'is_public' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-02 18:00:00',
                'updated_at' => '2024-12-02 18:00:00',
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'name' => 'Phố cổ Hội An',
                'description' => 'Khám phá vẻ đẹp cổ kính của phố cổ Hội An',
                'cover_photo_path' => 'albums/hoi_an_cover.jpg',
                'type' => 'manual',
                'auto_criteria' => null,
                'is_public' => false,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-03 15:00:00',
                'updated_at' => '2024-12-03 15:00:00',
            ],
            [
                'id' => 4,
                'user_id' => 1,
                'name' => 'Video timelapse',
                'description' => 'Tuyển tập video timelapse các thành phố Việt Nam',
                'cover_photo_path' => 'albums/timelapse_cover.jpg',
                'type' => 'manual',
                'auto_criteria' => null,
                'is_public' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-04 19:00:00',
                'updated_at' => '2024-12-04 19:00:00',
            ],
            [
                'id' => 5,
                'user_id' => 2,
                'name' => 'Ẩm thực đường phố',
                'description' => 'Khám phá ẩm thực đường phố Việt Nam',
                'cover_photo_path' => 'albums/street_food_cover.jpg',
                'type' => 'manual',
                'auto_criteria' => null,
                'is_public' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-05 20:00:00',
                'updated_at' => '2024-12-05 20:00:00',
            ],
            [
                'id' => 6,
                'user_id' => 4,
                'name' => 'GIF vui nhộn',
                'description' => 'Tuyển tập GIF động vui nhộn',
                'cover_photo_path' => 'albums/funny_gifs_cover.jpg',
                'type' => 'manual',
                'auto_criteria' => null,
                'is_public' => true,
                'is_deleted' => false,
                'deleted_at' => null,
                'created_at' => '2024-12-06 21:00:00',
                'updated_at' => '2024-12-06 21:00:00',
            ]
        ];

        DB::table('albums')->insert($albums);

        $albumMedia = [
            ['album_id' => 1, 'media_file_id' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['album_id' => 2, 'media_file_id' => 2, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['album_id' => 3, 'media_file_id' => 3, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['album_id' => 4, 'media_file_id' => 4, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['album_id' => 5, 'media_file_id' => 5, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['album_id' => 6, 'media_file_id' => 6, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('album_media')->insert($albumMedia);

        $mediaTags = [
            ['name' => 'phong cảnh', 'slug' => 'phong-canh', 'color' => '#4CAF50', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'du lịch', 'slug' => 'du-lich', 'color' => '#2196F3', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'thiên nhiên', 'slug' => 'thien-nhien', 'color' => '#8BC34A', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'biển', 'slug' => 'bien', 'color' => '#00BCD4', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'núi', 'slug' => 'nui', 'color' => '#795548', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'hoàng hôn', 'slug' => 'hoang-hon', 'color' => '#FF9800', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'thành phố', 'slug' => 'thanh-pho', 'color' => '#607D8B', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ẩm thực', 'slug' => 'am-thuc', 'color' => '#E91E63', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'vui nhộn', 'slug' => 'vui-nhon', 'color' => '#9C27B0', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'phố cổ', 'slug' => 'pho-co', 'color' => '#F44336', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('media_tags')->insert($mediaTags);

        $mediaFileTags = [
            ['media_file_id' => 1, 'media_tag_id' => 1, 'created_at' => now(), 'updated_at' => now()], // phong cảnh
            ['media_file_id' => 1, 'media_tag_id' => 2, 'created_at' => now(), 'updated_at' => now()], // du lịch
            ['media_file_id' => 1, 'media_tag_id' => 3, 'created_at' => now(), 'updated_at' => now()], // thiên nhiên
            ['media_file_id' => 1, 'media_tag_id' => 5, 'created_at' => now(), 'updated_at' => now()], // núi
            ['media_file_id' => 2, 'media_tag_id' => 1, 'created_at' => now(), 'updated_at' => now()], // phong cảnh
            ['media_file_id' => 2, 'media_tag_id' => 2, 'created_at' => now(), 'updated_at' => now()], // du lịch
            ['media_file_id' => 2, 'media_tag_id' => 4, 'created_at' => now(), 'updated_at' => now()], // biển
            ['media_file_id' => 2, 'media_tag_id' => 6, 'created_at' => now(), 'updated_at' => now()], // hoàng hôn
            ['media_file_id' => 3, 'media_tag_id' => 1, 'created_at' => now(), 'updated_at' => now()], // phong cảnh
            ['media_file_id' => 3, 'media_tag_id' => 2, 'created_at' => now(), 'updated_at' => now()], // du lịch
            ['media_file_id' => 3, 'media_tag_id' => 10, 'created_at' => now(), 'updated_at' => now()], // phố cổ
            ['media_file_id' => 4, 'media_tag_id' => 7, 'created_at' => now(), 'updated_at' => now()], // thành phố
            ['media_file_id' => 5, 'media_tag_id' => 8, 'created_at' => now(), 'updated_at' => now()], // ẩm thực
            ['media_file_id' => 5, 'media_tag_id' => 7, 'created_at' => now(), 'updated_at' => now()], // thành phố
            ['media_file_id' => 6, 'media_tag_id' => 9, 'created_at' => now(), 'updated_at' => now()], // vui nhộn
        ];

        DB::table('media_file_tags')->insert($mediaFileTags);

        $friendships = [
            ['user_id' => 1, 'friend_id' => 2, 'status' => 'accepted', 'accepted_at' => now()->subDays(30), 'created_at' => now()->subDays(35), 'updated_at' => now()->subDays(30)],
            ['user_id' => 2, 'friend_id' => 3, 'status' => 'accepted', 'accepted_at' => now()->subDays(15), 'created_at' => now()->subDays(20), 'updated_at' => now()->subDays(15)],
            ['user_id' => 3, 'friend_id' => 4, 'status' => 'accepted', 'accepted_at' => now()->subDays(7), 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(7)],
            ['user_id' => 1, 'friend_id' => 3, 'status' => 'pending', 'accepted_at' => null, 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],
            ['user_id' => 4, 'friend_id' => 2, 'status' => 'pending', 'accepted_at' => null, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['user_id' => 1, 'friend_id' => 5, 'status' => 'blocked', 'accepted_at' => null, 'created_at' => now()->subWeeks(2), 'updated_at' => now()->subWeeks(2)],
            ['user_id' => 1, 'friend_id' => 4, 'status' => 'accepted', 'accepted_at' => now()->subDays(45), 'created_at' => now()->subDays(50), 'updated_at' => now()->subDays(45)],
        ];

        DB::table('friendships')->insert($friendships);

        $shares = [
            [
                'user_id' => 1,
                'share_token' => 'sapa_hoang_liem_2024',
                'shareable_type' => 'media_file',
                'shareable_id' => 1,
                'permission' => 'view',
                'access_type' => 'public',
                'expires_at' => now()->addDays(30),
                'view_count' => 15,
                'is_active' => true,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'user_id' => 2,
                'share_token' => 'nha_trang_sunset_2024',
                'shareable_type' => 'media_file',
                'shareable_id' => 2,
                'permission' => 'view',
                'access_type' => 'public',
                'expires_at' => now()->addDays(15),
                'view_count' => 28,
                'is_active' => true,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'user_id' => 1,
                'share_token' => 'album_sapa_2024',
                'shareable_type' => 'album',
                'shareable_id' => 1,
                'permission' => 'view',
                'access_type' => 'public',
                'expires_at' => now()->addDays(60),
                'view_count' => 89,
                'is_active' => true,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
        ];

        DB::table('shares')->insert($shares);

        $mediaComments = [
            [
                'media_file_id' => 1,
                'user_id' => 2,
                'comment' => 'Ảnh đẹp quá! Chụp ở đâu vậy?',
                'parent_id' => null,
                'is_edited' => false,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'media_file_id' => 1,
                'user_id' => 3,
                'comment' => 'Tuyệt vời! Cảm ơn bạn đã chia sẻ.',
                'parent_id' => null,
                'is_edited' => false,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'media_file_id' => 2,
                'user_id' => 1,
                'comment' => 'Màu sắc rất đẹp, góc chụp hay quá!',
                'parent_id' => null,
                'is_edited' => false,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'media_file_id' => 3,
                'user_id' => 4,
                'comment' => 'Tôi cũng muốn đi chỗ này một lần.',
                'parent_id' => null,
                'is_edited' => false,
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
        ];

        DB::table('media_comments')->insert($mediaComments);

        $mediaFavorites = [
            ['media_file_id' => 1, 'user_id' => 2, 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
            ['media_file_id' => 1, 'user_id' => 3, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['media_file_id' => 2, 'user_id' => 1, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['media_file_id' => 2, 'user_id' => 4, 'created_at' => now()->subHours(12), 'updated_at' => now()->subHours(12)],
            ['media_file_id' => 3, 'user_id' => 2, 'created_at' => now()->subHours(6), 'updated_at' => now()->subHours(6)],
            ['media_file_id' => 6, 'user_id' => 1, 'created_at' => now()->subHours(3), 'updated_at' => now()->subHours(3)],
        ];

        DB::table('media_favorites')->insert($mediaFavorites);

        $mediaViews = [];
        for ($i = 0; $i < 50; $i++) {
            $mediaViews[] = [
                'media_file_id' => rand(1, 6),
                'user_id' => rand(1, 5),
                'ip_address' => '192.168.1.' . rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'viewed_at' => now()->subDays(rand(1, 30)),
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(1, 30)),
            ];
        }
        DB::table('media_views')->insert($mediaViews);

        $notifications = [
            [
                'user_id' => 1,
                'type' => 'friend_request',
                'title' => 'Lời mời kết bạn mới',
                'message' => 'Trần Thị Bình đã gửi lời mời kết bạn cho bạn.',
                'data' => json_encode(['friend_id' => 2, 'friend_name' => 'Trần Thị Bình']),
                'is_read' => false,
                'read_at' => null,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'user_id' => 1,
                'type' => 'media_liked',
                'title' => 'Ảnh được yêu thích',
                'message' => 'Lê Minh Cường đã thích ảnh "Hoàng liêm Sapa" của bạn.',
                'data' => json_encode(['media_id' => 1, 'liker_id' => 3, 'liker_name' => 'Lê Minh Cường']),
                'is_read' => true,
                'read_at' => now()->subHours(2),
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(2),
            ],
            [
                'user_id' => 2,
                'type' => 'friend_accepted',
                'title' => 'Lời mời kết bạn được chấp nhận',
                'message' => 'Nguyễn Văn An đã chấp nhận lời mời kết bạn của bạn.',
                'data' => json_encode(['friend_id' => 1, 'friend_name' => 'Nguyễn Văn An']),
                'is_read' => true,
                'read_at' => now()->subDays(1),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(1),
            ],
            [
                'user_id' => 3,
                'type' => 'storage_warning',
                'title' => 'Cảnh báo dung lượng lưu trữ',
                'message' => 'Dung lượng lưu trữ của bạn đã sử dụng 80%. Hãy xem xét nâng cấp gói lưu trữ.',
                'data' => json_encode(['used_percentage' => 80, 'used_storage' => '1GB', 'max_storage' => '5GB']),
                'is_read' => false,
                'read_at' => null,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ],
        ];

        DB::table('notifications')->insert($notifications);
    }
};