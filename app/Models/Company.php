<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public $fillable = [
        'name',
        'address',
        'phone',
        'email',
    ];

    public function user()
    {
        return $this->hasMany(User::class);
    }
}
