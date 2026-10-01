<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = ['bcc_emails'];

    protected $casts = ['bcc_emails' => 'array'];

    public static function bccRecipients(): array
    {
        $setting = static::query()->first();

        return $setting ? $setting->bcc_emails : config('billing.bcc', []);
    }
}
