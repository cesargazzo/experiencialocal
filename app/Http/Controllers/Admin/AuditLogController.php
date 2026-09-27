<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tipo' => ['nullable', Rule::in(array_keys(AuditLog::MODEL_LABELS))],
            'id' => ['nullable', 'string', 'max:64'],
            'evento' => ['nullable', Rule::in(array_keys(AuditLog::EVENT_LABELS))],
            'quien' => ['nullable', 'string', 'max:255'],
        ]);

        $logs = AuditLog::query()
            ->with('user')
            ->when($filters['tipo'] ?? null, fn ($q, $type) => $q->where('auditable_type', $type))
            ->when($filters['id'] ?? null, fn ($q, $id) => $q->where('auditable_id', $id))
            ->when($filters['evento'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['quien'] ?? null, fn ($q, $who) => $q->whereIn('user_id', User::query()->where('email', 'ilike', "%{$who}%")->orWhere('name', 'ilike', "%{$who}%")->select('id')))
            ->latest('created_at')
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.audit-log', [
            'logs' => $logs,
            'filters' => $filters,
            'retentionDays' => (int) config('tinku.audit_log_days'),
        ]);
    }
}
