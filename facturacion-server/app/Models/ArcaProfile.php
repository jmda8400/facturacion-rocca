<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArcaProfile extends Model
{
    protected $fillable = ['slug', 'name', 'cuit', 'sales_point', 'business_name', 'address', 'vat_condition', 'gross_income', 'activity_started_at', 'certificate_path', 'private_key_path', 'ta_path', 'active'];

    protected $casts = ['active' => 'boolean', 'activity_started_at' => 'date'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
