<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingClient extends Model
{
    protected $fillable = ['slug', 'name', 'username', 'password_hash', 'active', 'default_arca_profile_id'];

    protected $hidden = ['password_hash'];

    protected $casts = ['active' => 'boolean'];

    public function defaultArcaProfile() { return $this->belongsTo(ArcaProfile::class, 'default_arca_profile_id'); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function accessTokens() { return $this->hasMany(BillingAccessToken::class); }
}
