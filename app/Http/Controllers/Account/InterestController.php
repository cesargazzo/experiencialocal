<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
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
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'categories' => ['array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
            'provinces' => ['array'],
            'provinces.*' => ['integer', Rule::exists('provinces', 'id')],
        ]);

        $user = $request->user();
        $user->interestedCategories()->sync($data['categories'] ?? []);
        $user->interestedProvinces()->sync($data['provinces'] ?? []);
        $user->forceFill(['interest_alerts' => $request->boolean('interest_alerts')])->save();

        return back()->with('status', $user->interest_alerts && $user->hasInterests()
            ? 'Guardamos tus intereses. Te avisamos cuando aparezca algo.'
            : 'Guardamos tus intereses.');
    }
}
