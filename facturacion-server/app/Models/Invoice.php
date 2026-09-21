<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = ['idempotency_key', 'arca_profile_id', 'external_reference', 'status', 'invoice_type', 'request_payload', 'voucher_number', 'cae', 'cae_expires_at', 'pdf_path', 'error', 'emailed_at'];

    protected $casts = ['request_payload' => 'array', 'cae_expires_at' => 'date', 'emailed_at' => 'datetime'];

    public function profile()
    {
        return $this->belongsTo(ArcaProfile::class, 'arca_profile_id');
    }
}
