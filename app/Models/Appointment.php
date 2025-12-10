<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'user_id',
        'session_id',
        'date',
        'scheduled_time',
        'start_time',
        'end_time',
        'type',
        'status',
        'observations',
        'session_notes',
        'attachments',
    ];

    protected $casts = [
        'date' => 'date',
        'attachments' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    protected static function booted()
    {
        static::updated(function ($appointment) {
            if ($appointment->isDirty('status') && $appointment->status === 'Realizado') {
                if ($appointment->session) {
                    $appointment->session->increment('completed_appointments');

                    if ($appointment->session->completed_appointments >= $appointment->session->total_appointments) {
                        $appointment->session->update(['status' => 'Concluída']);
                    }
                }
            }
        });
    }
}
