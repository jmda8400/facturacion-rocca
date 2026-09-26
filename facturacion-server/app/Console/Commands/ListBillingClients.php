<?php
namespace App\Console\Commands;
use App\Models\BillingClient; use Illuminate\Console\Command;
class ListBillingClients extends Command { protected $signature='billing:client:list'; protected $description='Lista clientes sin exponer secretos'; public function handle():int{$this->table(['Slug','Nombre','Username','Estado','Perfil'],BillingClient::with('defaultArcaProfile')->get()->map(fn($c)=>[$c->slug,$c->name,$c->username,$c->active?'activo':'inactivo',$c->defaultArcaProfile?->slug??'-']));return self::SUCCESS;} }
