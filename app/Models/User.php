<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'password',
        'phone',
        'cpf',
        'specialty',
        'avatar',
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
        ];
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }
}
