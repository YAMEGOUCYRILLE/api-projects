<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'departure',
        'destination',
        'departure_date',
        'arrival_date',
        'price',
        'available_seats',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'datetime',
            'arrival_date' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByDateRange($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->where('departure_date', '>=', Carbon::parse($startDate)->startOfDay());
        }
        
        if ($endDate) {
            $query->where('departure_date', '<=', Carbon::parse($endDate)->endOfDay());
        }
        
        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}