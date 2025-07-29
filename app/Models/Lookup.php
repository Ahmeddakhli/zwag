<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Lookup extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'parent_id'];
    
    public $translatable = ['title'];
    
    protected $casts = [
        'title' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(Lookup::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Lookup::class, 'parent_id');
    }
}