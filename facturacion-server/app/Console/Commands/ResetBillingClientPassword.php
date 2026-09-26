<?php
namespace App\Console\Commands;
use App\Models\BillingClient; use Illuminate\Console\Command; use Illuminate\Support\Facades\Hash; use Illuminate\Support\Str;
class ResetBillingClientPassword extends Command { protected $signature='billing:client:reset-password {slug}'; protected $description='Rota la contraseña y revoca tokens'; public function handle():int{$c=BillingClient::where('slug',$this->argument('slug'))->firstOrFail();$p=Str::password(32);$c->update(['password_hash'=>Hash::make($p)]);$c->accessTokens()->whereNull('revoked_at')->update(['revoked_at'=>now()]);$this->warn('Guardá esta contraseña ahora; no volverá a mostrarse:');$this->line($p);return self::SUCCESS;} }
