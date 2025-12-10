<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'day_of_week',
        'time',
    ];

    protected $casts = [
        'time' => 'datetime:H:i',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}
