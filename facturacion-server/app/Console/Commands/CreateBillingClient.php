<?php
namespace App\Console\Commands;
use App\Models\BillingClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class CreateBillingClient extends Command {
 protected $signature='billing:client:create {slug} {name} {--username=}'; protected $description='Crea un cliente y muestra su contraseña una sola vez';
 public function handle(): int { $slug=Str::slug($this->argument('slug')); $password=Str::password(32); $username=$this->option('username') ?: $slug; if(BillingClient::where('slug',$slug)->orWhere('username',$username)->exists()){ $this->error('El slug o username ya existe.'); return self::FAILURE; } BillingClient::create(['slug'=>$slug,'name'=>$this->argument('name'),'username'=>$username,'password_hash'=>Hash::make($password)]); $this->warn('Guardá esta contraseña ahora; no volverá a mostrarse:'); $this->line($password); return self::SUCCESS; }
}
