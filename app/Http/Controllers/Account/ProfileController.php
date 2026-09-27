<?php

namespace App\Http\Controllers\Account;

use App\Enums\DietaryOption;
use App\Enums\VerificationLevel;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Province;
use App\Models\User;
use App\Support\CountryList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('avatar');

        return view('account.profile', [
            'user' => $user,
            'nameLocked' => $this->identityIsLocked($user),
            'birthDateLocked' => $this->identityIsLocked($user) && $user->birth_date !== null,
            'dietaryOptions' => DietaryOption::cases(),
            'provincesByCountry' => Province::query()
                ->whereIn('country_code', Country::active()->pluck('code'))
                ->orderBy('name')
                ->get(['id', 'name', 'country_code'])
                ->groupBy('country_code')
                ->map(fn ($provinces) => $provinces->pluck('name', 'id')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $locked = $this->identityIsLocked($user);
        $birthDateLocked = $locked && $user->birth_date !== null;

        $isArgentina = $request->input('country_code') === 'AR';

        if (is_string($request->input('postal_code'))) {
            $postalCode = mb_strtoupper(trim($request->input('postal_code')));
            $request->merge(['postal_code' => $isArgentina ? str_replace([' ', '-'], '', $postalCode) : $postalCode]);
        }

        $countryHasProvinces = Province::query()->where('country_code', $request->input('country_code'))->exists();

        $data = $request->validate([
            'first_name' => $locked ? ['prohibited'] : ['required', 'string', 'max:60'],
            'last_name' => $locked ? ['prohibited'] : ['required', 'string', 'max:80'],
            'birth_date' => $birthDateLocked ? ['prohibited'] : ['required', ...User::birthDateRules()],
            'country_code' => ['required', Rule::in(CountryList::codes())],
            'province_id' => $countryHasProvinces
                ? ['required', Rule::exists('provinces', 'id')->where('country_code', $request->input('country_code'))]
                : ['exclude'],
            'city' => ['required', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:12', $isArgentina ? 'regex:/^([A-Z]\d{4}[A-Z]{3}|\d{4})$/' : 'regex:/^[A-Z0-9][A-Z0-9 \-]{1,10}[A-Z0-9]$/'],
        ], [
            'birth_date.before_or_equal' => 'Tenés que tener al menos '.config('tinku.min_age').' años para usar Tinku.',
            'birth_date.after' => 'Revisá la fecha de nacimiento.',
            'first_name.prohibited' => 'Tu nombre quedó validado con tu documento.',
            'last_name.prohibited' => 'Tu apellido quedó validado con tu documento.',
            'birth_date.prohibited' => 'Tu fecha de nacimiento quedó validada con tu documento.',
            'province_id.required' => 'Elegí tu provincia.',
            'province_id.exists' => 'Elegí una provincia de la lista.',
            'city.required' => 'Contanos en qué ciudad vivís.',
            'postal_code.regex' => $isArgentina
                ? 'Revisá el código postal: son 4 números (5360) o el formato nuevo (F5360ABC).'
                : 'Revisá el código postal.',
        ]);

        $user->update([...$data, 'province_id' => $data['province_id'] ?? null, 'postal_code' => $data['postal_code'] ?? null]);

        return back()->with('status', 'Guardamos tus datos.');
    }

    /** Restricciones alimentarias: opcionales y se pueden cambiar siempre. */
    public function updateDiet(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dietary_needs' => ['nullable', 'array'],
            'dietary_needs.*' => [Rule::enum(DietaryOption::class)],
            'food_allergies' => ['nullable', 'string', 'max:300'],
        ], [
            'food_allergies.max' => 'Resumilo en 300 caracteres como máximo.',
        ]);

        $request->user()->update([
            'dietary_needs' => array_values(array_unique($data['dietary_needs'] ?? [])) ?: null,
            'food_allergies' => $data['food_allergies'] ?? null,
        ]);

        return back()->with('status', 'Guardamos tu alimentación.');
    }

    /**
     * Con el documento validado, nombre y fecha de nacimiento quedan fijos: son los del documento.
     * Dónde vive se puede cambiar siempre, porque la gente se muda.
     */
    private function identityIsLocked(User $user): bool
    {
        return $user->hasVerificationLevel(VerificationLevel::Document);
    }
}
