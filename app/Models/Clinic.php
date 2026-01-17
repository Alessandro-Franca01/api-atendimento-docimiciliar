<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\Factories\HasFactory;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cnpj',
        'email',
        'phone',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
