<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'user_id',
        'appointment_id',
        'session_id',
        'amount',
        'payment_date',
        'payment_method',
        'status',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            if ($payment->status === 'Pago' && $payment->session) {
                $payment->session->increment('paid_value', $payment->amount);
            }
        });

        static::updated(function ($payment) {
            if ($payment->isDirty('status') && $payment->status === 'Pago' && $payment->session) {
                $payment->session->increment('paid_value', $payment->amount);
            }
        });
    }
}
