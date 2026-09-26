<?php

namespace App\Http\Controllers;

use App\Models\ArcaProfile;
use App\Models\BillingEvent;
use App\Models\Invoice;
use App\Services\BillingEventLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingsController
{
    public function index(BillingEventLogger $events)
    {
        $profiles = ArcaProfile::latest()->get()->each(function (ArcaProfile $profile) {
            $profile->last_renewal_at = null;
            $profile->next_renewal_at = null;

            if ($profile->ta_path && Storage::exists($profile->ta_path)) {
                $profile->last_renewal_at = CarbonImmutable::createFromTimestamp(Storage::lastModified($profile->ta_path));
                $profile->next_renewal_at = $profile->last_renewal_at->addHours(6);
            }
        });

        $events->record('timer.activated', 'Cronómetro de renovación activado en el panel.');
        $history = BillingEvent::latest('id')->limit(100)->get();
        $invoices = Invoice::with(['billingClient', 'profile'])->latest()->limit(50)->get();
        return view('settings.index', compact('profiles', 'history', 'invoices'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => ['required', 'string', 'max:100'], 'cuit' => ['required', 'regex:/^\d{11}$/'], 'sales_point' => ['required', 'integer', 'min:1'],
            'business_name' => ['required', 'string', 'max:200'], 'address' => ['required', 'string', 'max:300'], 'vat_condition' => ['required', 'string', 'max:100'],
            'gross_income' => ['nullable', 'string', 'max:100'], 'activity_started_at' => ['nullable', 'date'],
            'certificate' => ['required', 'file', 'max:100', 'extensions:crt'],
            'private_key' => ['required', 'file', 'max:100', 'extensions:key'],
        ], [
            'certificate.extensions' => 'El certificado debe ser un archivo .crt.',
            'private_key.extensions' => 'La clave privada debe ser un archivo .key.',
        ]);

        $this->validateCredentials($r);
        $slug = $this->uniqueSlug($d['name']);
        $base = 'arca/'.$slug;
        $cert = $r->file('certificate')->storeAs($base, 'certificate.crt');
        $key = $r->file('private_key')->storeAs($base, 'private.key');
        ArcaProfile::create([
            'slug' => $slug,
            'name' => $d['name'],
            'cuit' => $d['cuit'], 'sales_point' => $d['sales_point'], 'business_name' => $d['business_name'], 'address' => $d['address'],
            'vat_condition' => $d['vat_condition'], 'gross_income' => $d['gross_income'] ?? null, 'activity_started_at' => $d['activity_started_at'] ?? null,
            'certificate_path' => $cert,
            'private_key_path' => $key,
            'ta_path' => $base.'/TA.xml',
        ]);

        return back()->with('status', 'Punto de venta registrado.');
    }

    public function update(Request $r, ArcaProfile $profile)
    {
        $d = $r->validate(['name'=>'required|string|max:100','cuit'=>['required','regex:/^\d{11}$/'],'sales_point'=>'required|integer|min:1','business_name'=>'required|string|max:200','address'=>'required|string|max:300','vat_condition'=>'required|string|max:100','gross_income'=>'nullable|string|max:100','activity_started_at'=>'nullable|date','certificate'=>'nullable|file|max:100|extensions:crt','private_key'=>'nullable|file|max:100|extensions:key']);
        if ($r->hasFile('certificate') xor $r->hasFile('private_key')) return back()->withErrors(['certificate'=>'Para rotar credenciales debe cargar certificado y clave juntos.'])->withInput();
        if ($r->hasFile('certificate')) { $this->validateCredentials($r); $base='arca/'.$profile->slug; $d['certificate_path']=$r->file('certificate')->storeAs($base,'certificate.crt'); $d['private_key_path']=$r->file('private_key')->storeAs($base,'private.key'); Storage::delete($profile->ta_path); }
        unset($d['certificate'], $d['private_key']); $profile->update($d);
        return back()->with('status','Punto de venta actualizado.');
    }

    private function validateCredentials(Request $request): void
    {
        $cert = @openssl_x509_read((string) file_get_contents($request->file('certificate')->getRealPath()));
        $key = @openssl_pkey_get_private((string) file_get_contents($request->file('private_key')->getRealPath()));
        if (! $cert) throw ValidationException::withMessages(['certificate' => 'El certificado .crt no puede ser leído por OpenSSL.']);
        if (! $key) throw ValidationException::withMessages(['private_key' => 'La private key .key no puede ser leída por OpenSSL.']);
        if (! openssl_x509_check_private_key($cert, $key)) throw ValidationException::withMessages(['private_key' => 'El certificado y la private key no corresponden entre sí.']);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'punto-de-venta';
        $slug = $base;
        $suffix = 2;

        while (ArcaProfile::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function destroy(ArcaProfile $profile)
    {
        abort_if($profile->invoices()->exists(), 409, 'El perfil tiene facturas.');
        Storage::deleteDirectory('arca/'.$profile->slug);
        $profile->delete();

        return back()->with('status', 'Perfil eliminado.');
    }
}
