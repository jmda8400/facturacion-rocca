<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingAccessToken extends Model
{
    protected $fillable = ['billing_client_id', 'token_hash', 'expires_at', 'last_used_at', 'revoked_at'];
    protected $hidden = ['token_hash'];
    protected $casts = ['expires_at' => 'datetime', 'last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    public function billingClient() { return $this->belongsTo(BillingClient::class); }
}
