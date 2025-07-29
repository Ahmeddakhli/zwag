<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class MaritalStatus extends Model
{
    use HasTranslations;

    // Define which attributes should be translatable
    public $translatable = ['title'];
    
    protected $fillable = ['title'];
    
    protected $casts = [
        'title' => 'array',
    ];
}
