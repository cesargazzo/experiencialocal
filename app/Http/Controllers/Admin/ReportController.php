<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mensajes denunciados. El equipo lee solo el mensaje denunciado, nunca la
 * conversación entera, y cada lectura queda registrada.
 */
class ReportController extends Controller
{
    public function index(Request $request, SecurityLog $securityLog): View
    {
        $reports = Message::query()
            ->with(['sender', 'conversation.experience', 'conversation.guest', 'conversation.hostUser'])
            ->whereNotNull('reported_at')
            ->latest('reported_at')
            ->paginate(30);

        if ($reports->isNotEmpty()) {
            $securityLog->record('admin.reports_viewed', $request->user(), ['message_ids' => $reports->pluck('id')->all()]);
        }

        return view('admin.reports', ['reports' => $reports]);
    }

    public function dismiss(Request $request, Message $message, SecurityLog $securityLog): RedirectResponse
    {
        $message->forceFill(['reported_at' => null, 'report_reason' => null, 'reported_by' => null])->save();
        $securityLog->record('admin.report_dismissed', $request->user(), ['message_id' => $message->id]);

        return back()->with('status', 'Descartamos la denuncia.');
    }
}
