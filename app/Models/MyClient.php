<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class MyClient extends Model
{
    use HasFactory;

    protected $table = 'my_client';

    protected $fillable = [
        'name',
        'slug',
        'is_project',
        'self_capture',
        'client_prefix',
        'client_logo',
        'address',
        'phone_number',
        'city',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        // Menggunakan event untuk menangani Redis saat data dibuat, diperbarui, atau dihapus
        static::created(function ($client) {
            Redis::set($client->slug, json_encode($client)); // Simpan data sebagai JSON
        });

        static::updated(function ($client) {
            Redis::del($client->slug); // Hapus data Redis yang lama
            Redis::set($client->slug, json_encode($client)); // Simpan data yang diperbarui
        });

        static::deleted(function ($client) {
            Redis::del($client->slug); // Hapus data Redis ketika data dihapus
        });
    }
}
