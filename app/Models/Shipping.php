<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $table = 'shipping_charges';
    use HasFactory;

    public function country()
    {
        return $this->hasMany(Country::class);
    }
}
