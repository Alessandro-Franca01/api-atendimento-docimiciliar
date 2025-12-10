<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'user_id',
        'title',
        'total_appointments',
        'completed_appointments',
        'total_value',
        'paid_value',
        'start_date',
        'end_date',
        'status',
        'observations',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_value' => 'decimal:2',
        'paid_value' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function schedules()
    {
        return $this->hasMany(SessionSchedule::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getRemainingBalanceAttribute()
    {
        return $this->total_value - $this->paid_value;
    }
}
