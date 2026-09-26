<?php
namespace App\Console\Commands;
use App\Models\BillingClient; use Illuminate\Console\Command;
class DisableBillingClient extends Command { protected $signature='billing:client:disable {slug}'; protected $description='Deshabilita un cliente y revoca sus tokens'; public function handle():int{$c=BillingClient::where('slug',$this->argument('slug'))->firstOrFail();$c->update(['active'=>false]);$c->accessTokens()->whereNull('revoked_at')->update(['revoked_at'=>now()]);$this->info('Cliente deshabilitado y tokens revocados.');return self::SUCCESS;} }
