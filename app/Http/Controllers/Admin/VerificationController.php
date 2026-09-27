<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(): View
    {
        return view('admin.verifications', [
            'pending' => IdentityVerification::with('user')->where('status', VerificationStatus::Pending)->oldest('submitted_at')->get(),
        ]);
    }

    public function approve(Request $request, IdentityVerification $verification, VerificationService $verifications): RedirectResponse
    {
        $verifications->approve($verification, $request->user(), ['reviewed_in' => 'admin']);

        return back()->with('status', 'Verificación aprobada para '.$verification->user->name.'.');
    }

    public function reject(Request $request, IdentityVerification $verification, VerificationService $verifications): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $verifications->reject($verification, $data['reason'], $request->user());

        return back()->with('status', 'Verificación rechazada.');
    }
}
