<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlingToken extends Model
{
    protected $fillable = [
        'access_token','refresh_token','token_type','expires_in','expires_at','scope'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
