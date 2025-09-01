<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'departure_city',
        'arrival_city',
        'departure_time',
        'available_seats',
        'total_seats',
        'price_per_seat',
        'description',
        'status',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'price_per_seat' => 'decimal:2',
    ];

    // Relations
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('available_seats', '>', 0);
    }
}