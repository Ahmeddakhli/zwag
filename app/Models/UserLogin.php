<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLogin extends Model
{
      protected $fillable = [
        'user_id',
        'user_ip',
        'longitude',
        'latitude',
        'city',
        'country_code',
        'country',
        'browser',
        'os',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
