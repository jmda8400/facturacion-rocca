<?php
namespace App\Console\Commands;
use App\Models\ArcaProfile; use App\Models\BillingClient; use Illuminate\Console\Command;
class SetBillingClientProfile extends Command { protected $signature='billing:client:set-profile {slug} {profile-slug}'; protected $description='Asigna el perfil ARCA permitido y predeterminado'; public function handle():int{$c=BillingClient::where('slug',$this->argument('slug'))->firstOrFail();$p=ArcaProfile::where('slug',$this->argument('profile-slug'))->firstOrFail();$c->update(['default_arca_profile_id'=>$p->id]);$this->info("Perfil {$p->slug} asignado.");return self::SUCCESS;} }
