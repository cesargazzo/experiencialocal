<?php

namespace App\Http\Controllers\Account;

use App\Enums\ExperienceFeature;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InterestController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['interestedCategories', 'interestedProvinces']);

        return view('account.interests', [
            'user' => $user,
            'categories' => Category::orderBy('sort_order')->get(),
            'countries' => Country::active()->with('provinces')->orderBy('name')->get(),
            'selectedCategories' => $user->interestedCategories->pluck('id')->all(),
            'selectedProvinces' => $user->interestedProvinces->pluck('id')->all(),
            'features' => ExperienceFeature::cases(),
            'selectedFeatures' => $user->required_features?->map->value->all() ?? [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'categories' => ['array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
            'provinces' => ['array'],
            'provinces.*' => ['integer', Rule::exists('provinces', 'id')],
            'features' => ['array'],
            'features.*' => [Rule::enum(ExperienceFeature::class)],
        ]);

        $user = $request->user();
        $before = ['categorías' => $user->interestedCategories()->pluck('categories.id')->sort()->values()->all(), 'provincias' => $user->interestedProvinces()->pluck('provinces.id')->sort()->values()->all()];
        $user->interestedCategories()->sync($data['categories'] ?? []);
        $user->interestedProvinces()->sync($data['provinces'] ?? []);
        $after = ['categorías' => collect($data['categories'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all(), 'provincias' => collect($data['provinces'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all()];
        if ($before !== $after) {
            AuditLog::record($user, 'interests.updated', $before, $after);
        }
        $user->forceFill([
            'interest_alerts' => $request->boolean('interest_alerts'),
            'required_features' => array_values(array_unique($data['features'] ?? [])) ?: null,
        ])->save();

        return back()->with('status', $user->interest_alerts && $user->hasInterests()
            ? 'Guardamos tus intereses. Te avisamos cuando aparezca algo.'
            : 'Guardamos tus intereses.');
    }
}
