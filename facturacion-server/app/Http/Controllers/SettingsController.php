<?php

namespace App\Http\Controllers;

use App\Models\ArcaProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsController
{
    public function index()
    {
        $profiles = ArcaProfile::latest()->get()->each(function (ArcaProfile $profile) {
            $profile->last_renewal_at = null;
            $profile->next_renewal_at = null;

            if ($profile->ta_path && Storage::exists($profile->ta_path)) {
                $profile->last_renewal_at = CarbonImmutable::createFromTimestamp(Storage::lastModified($profile->ta_path));
                $profile->next_renewal_at = $profile->last_renewal_at->addHours(6);
            }
        });

        return view('settings.index', compact('profiles'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => ['required', 'string', 'max:100'],
            'certificate' => ['required', 'file', 'max:100', 'extensions:crt'],
            'private_key' => ['required', 'file', 'max:100', 'extensions:key'],
        ], [
            'certificate.extensions' => 'El certificado debe ser un archivo .crt.',
            'private_key.extensions' => 'La clave privada debe ser un archivo .key.',
        ]);

        $slug = $this->uniqueSlug($d['name']);
        $base = 'arca/'.$slug;
        $cert = $r->file('certificate')->storeAs($base, 'certificate.crt');
        $key = $r->file('private_key')->storeAs($base, 'private.key');
        ArcaProfile::create([
            'slug' => $slug,
            'name' => $d['name'],
            'cuit' => '00000000000',
            'sales_point' => 1,
            'business_name' => $d['name'],
            'address' => '-',
            'vat_condition' => 'IVA Responsable Inscripto',
            'certificate_path' => $cert,
            'private_key_path' => $key,
            'ta_path' => $base.'/TA.xml',
        ]);

        return back()->with('status', 'Punto de venta registrado.');
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
