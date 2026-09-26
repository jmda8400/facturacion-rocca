<?php
namespace App\Console\Commands;
use App\Models\BillingClient; use Illuminate\Console\Command;
class EnableBillingClient extends Command { protected $signature='billing:client:enable {slug}'; protected $description='Habilita un cliente'; public function handle():int{$c=BillingClient::where('slug',$this->argument('slug'))->firstOrFail();$c->update(['active'=>true]);$this->info('Cliente habilitado.');return self::SUCCESS;} }
