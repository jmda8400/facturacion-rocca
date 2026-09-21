<?php

namespace App\Http\Controllers;

use App\Models\ArcaProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $d = $r->validate(['slug' => ['required', 'alpha_dash', Rule::unique('arca_profiles')], 'name' => 'required|max:100', 'cuit' => 'required|digits:11', 'sales_point' => 'required|integer|min:1', 'business_name' => 'required', 'address' => 'required', 'vat_condition' => 'required', 'gross_income' => 'nullable', 'activity_started_at' => 'nullable|date', 'certificate' => 'required|file|max:100', 'private_key' => 'required|file|max:100']);
        $base = 'arca/'.$d['slug'];
        $cert = $r->file('certificate')->storeAs($base, 'certificate.crt');
        $key = $r->file('private_key')->storeAs($base, 'private.key');
        ArcaProfile::create([...$d, 'certificate_path' => $cert, 'private_key_path' => $key, 'ta_path' => $base.'/TA.xml']);

        return back()->with('status', 'Perfil ARCA creado.');
    }

    public function destroy(ArcaProfile $profile)
    {
        abort_if($profile->invoices()->exists(), 409, 'El perfil tiene facturas.');
        Storage::deleteDirectory('arca/'.$profile->slug);
        $profile->delete();

        return back()->with('status', 'Perfil eliminado.');
    }
}
