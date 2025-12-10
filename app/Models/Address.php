<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'type',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
