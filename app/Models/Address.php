<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;


    public function getZone(){
        return $this->hasOne('App\Models\Zone', 'address_id');
    }
}
