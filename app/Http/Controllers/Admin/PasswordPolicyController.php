<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SecurityLog;
use App\Support\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordPolicyController extends Controller
{
    public function edit(): View
    {
        return view('admin.password-policy', ['policy' => PasswordPolicy::current()]);
    }

    public function update(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $data = $request->validate([
            'min_length' => ['required', 'integer', 'min:'.PasswordPolicy::ABSOLUTE_MIN_LENGTH, 'max:64'],
            'max_login_attempts' => ['required', 'integer', 'min:3', 'max:20'],
            'lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        foreach (['require_mixed_case', 'require_numbers', 'require_symbols', 'check_uncompromised'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $before = PasswordPolicy::current()->toArray();
        $after = PasswordPolicy::fromArray($data)->toArray();
        Setting::store(PasswordPolicy::SETTING_KEY, $after, $request->user());
        $securityLog->record('policy.updated', $request->user(), ['antes' => $before, 'después' => $after], null, 'warning');

        return back()->with('status', 'Guardamos la política de contraseñas. Aplica a las contraseñas nuevas.');
    }
}
