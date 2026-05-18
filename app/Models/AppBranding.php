<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppBranding extends Model
{
    protected $fillable = [
        'app_name',
        'short_name',
        'logo_path',
        'hero_kicker',
        'hero_heading',
        'hero_body',
        'hero_image_path',
        'login_heading',
        'login_subheading',
        'primary_color',
        'secondary_color',
        'accent_color',
        'chatbot_enabled',
    ];

    public static function defaults(): array
    {
        return [
            'app_name' => 'KIPS',
            'short_name' => 'K',
            'logo_path' => null,
            'hero_kicker' => 'Web-Based Internship Monitoring and Attendance Validation Information System',
            'hero_heading' => 'Seamlessly Bridging Education and Industry.',
            'hero_body' => 'KIPS is a comprehensive Monitoring and Attendance Validation System designed to ensure transparency, accountability, and real-time synchronization between students, schools, and industry partners.',
            'hero_image_path' => null,
            'login_heading' => 'Log in to your account',
            'login_subheading' => 'Use your registered account to access the dashboard.',
            'primary_color' => '#4f46e5',
            'secondary_color' => '#10b981',
            'accent_color' => '#f59e0b',
            'chatbot_enabled' => true,
        ];
    }

    public static function current(): self
    {
        static $cached = null;

        if ($cached instanceof self) {
            return $cached;
        }

        $defaults = static::defaults();

        if (!Schema::hasTable('app_brandings')) {
            $cached = new static($defaults);
            return $cached;
        }

        $cached = static::query()->firstOrCreate(
            ['id' => 1],
            $defaults
        );

        foreach ($defaults as $key => $value) {
            if (blank($cached->{$key}) && filled($value)) {
                $cached->{$key} = $value;
            }
        }

        return $cached;
    }

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->app_name ?: static::defaults()['app_name']));
    }

    public function getShortDisplayNameAttribute(): string
    {
        $short = trim((string) ($this->short_name ?? ''));

        if ($short !== '') {
            return $short;
        }

        return Str::upper(Str::substr($this->display_name, 0, 1) ?: 'K');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->resolveAssetUrl($this->logo_path);
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->resolveAssetUrl($this->hero_image_path);
    }

    protected function resolveAssetUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
