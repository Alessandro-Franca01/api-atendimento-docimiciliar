<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'status',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
