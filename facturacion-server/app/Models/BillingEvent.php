<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingEvent extends Model
{
    public $timestamps = false;
    protected $fillable = ['level', 'event', 'message', 'context', 'created_at'];
    protected $casts = ['context' => 'array', 'created_at' => 'datetime'];
}
