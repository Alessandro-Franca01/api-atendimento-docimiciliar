<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'cpf',
        'birth_date',
        'age',
        'avatar',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
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

    public function getFinancialStatusAttribute()
    {
        $pendingPayments = $this->payments()
            ->where('status', 'Pendente')
            ->orWhere('status', 'Atrasado')
            ->count();

        if ($pendingPayments === 0) {
            return 'Em dia';
        }

        $overduePayments = $this->payments()
            ->where('status', 'Atrasado')
            ->count();

        return $overduePayments > 0 ? 'Atrasado' : 'Pendente';
    }

    public function getTotalToPayAttribute()
    {
        return $this->payments()
            ->whereIn('status', ['Pendente', 'Atrasado'])
            ->sum('amount');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()
            ->where('status', 'Pago')
            ->sum('amount');
    }
}
