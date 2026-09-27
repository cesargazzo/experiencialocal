<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Exceptions\VerificationException;
use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Services\SecurityLog;
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

    public function approve(Request $request, IdentityVerification $verification, VerificationService $verifications, SecurityLog $securityLog): RedirectResponse
    {
        try {
            $verifications->approve($verification, $request->user(), ['reviewed_in' => 'admin']);
        } catch (VerificationException $e) {
            return back()->withErrors(['verification' => $e->getMessage()]);
        }
        $securityLog->record('verification.approved', $request->user(), ['verification_id' => $verification->id, 'type' => $verification->type->value, 'account' => $verification->user->email]);

        return back()->with('status', 'Verificación aprobada para '.$verification->user->name.'.');
    }

    public function reject(Request $request, IdentityVerification $verification, VerificationService $verifications, SecurityLog $securityLog): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $verifications->reject($verification, $data['reason'], $request->user());
        $securityLog->record('verification.rejected', $request->user(), ['verification_id' => $verification->id, 'type' => $verification->type->value, 'account' => $verification->user->email, 'reason' => $data['reason']]);

        return back()->with('status', 'Verificación rechazada.');
    }
}
