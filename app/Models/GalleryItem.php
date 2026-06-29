<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = [
        'title', 'url', 'thumbnail', 'type', 'format', 'platform', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function detectPlatform(string $url): string
    {
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($url, 'facebook.com') || str_contains($url, 'fb.watch')) {
            return 'facebook';
        }
        if (str_contains($url, 'instagram.com')) {
            return 'instagram';
        }
        return 'other';
    }

    public static function detectType(string $url, string $platform): string
    {
        if ($platform === 'youtube') return 'video';
        if ($platform === 'facebook') {
            return (str_contains($url, '/videos/') || str_contains($url, 'fb.watch')) ? 'video' : 'photo';
        }
        if ($platform === 'instagram') {
            return str_contains($url, '/reel/') ? 'video' : 'photo';
        }
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)(\?|$)/i', $url)) {
            return 'photo';
        }
        return 'video';
    }
}
