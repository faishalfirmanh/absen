<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaActivity extends Model
{
    use HasFactory;


    public function getUsers()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
