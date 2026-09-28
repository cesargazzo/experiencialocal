<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SecurityLogController extends Controller
{
    /** Grupos para filtrar el listado. */
    private const GROUPS = [
        'accesos' => ['login.succeeded', 'login.failed', 'login.locked', 'logout', 'register', '2fa.enabled', '2fa.disabled', '2fa.failed', '2fa.recovery_used', '2fa.recovery_regenerated'],
        'contrasenas' => ['password.changed', 'password.reset_requested', 'password.reset', 'password.reset_failed', 'password.temporary_issued', 'policy.updated'],
        'admin' => ['admin.granted', 'admin.revoked', 'verification.approved', 'verification.rejected', 'verification.revoked', 'user.validation_blocked', 'admin.user_viewed', 'log.downloaded', 'admin.reports_viewed', 'admin.report_dismissed', 'policy.updated'],
        'amenazas' => ['login.failed', 'login.locked', '2fa.failed', 'access.forbidden', 'request.throttled', 'probe.suspicious', 'probe.banned', 'password.reset_failed'],
        'errores' => ['error.server'],
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'grupo' => ['nullable', 'in:'.implode(',', array_keys(self::GROUPS))],
            'ip' => ['nullable', 'ip'],
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        $events = SecurityEvent::query()
            ->with('user')
            ->when($filters['grupo'] ?? null, fn ($q, $group) => $q->whereIn('type', self::GROUPS[$group]))
            ->when($filters['ip'] ?? null, fn ($q, $ip) => $q->where('ip', $ip))
            ->when($filters['email'] ?? null, fn ($q, $email) => $q->where('email', 'ilike', '%'.$email.'%'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $since = now()->subDay();
        $counts = SecurityEvent::query()
            ->where('created_at', '>=', $since)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $suspiciousIps = SecurityEvent::query()
            ->where('created_at', '>=', $since)
            ->whereIn('type', self::GROUPS['amenazas'])
            ->whereNotNull('ip')
            ->select('ip', DB::raw('count(*) as total'), DB::raw('count(distinct email) as accounts'), DB::raw('max(created_at) as last_seen'))
            ->groupBy('ip')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('admin.security-log', [
            'events' => $events,
            'counts' => $counts,
            'suspiciousIps' => $suspiciousIps,
            'filters' => $filters,
            'groups' => array_keys(self::GROUPS),
            'retentionDays' => (int) config('tinku.security_log_days'),
        ]);
    }
}
