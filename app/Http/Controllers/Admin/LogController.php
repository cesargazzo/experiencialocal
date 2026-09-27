<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SecurityLog;
use App\Support\LogReader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController extends Controller
{
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public function index(Request $request, LogReader $logs): View
    {
        $files = $logs->files();
        $filters = $request->validate([
            'archivo' => ['nullable', 'string', Rule::in($files->pluck('name'))],
            'nivel' => ['nullable', Rule::in(self::LEVELS)],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $file = $filters['archivo'] ?? $files->first()['name'] ?? null;

        return view('admin.logs', [
            'files' => $files,
            'file' => $file,
            'filters' => $filters,
            'levels' => self::LEVELS,
            'entries' => $file ? $logs->entries($file, $filters['nivel'] ?? null, $filters['q'] ?? null) : collect(),
        ]);
    }

    public function download(Request $request, string $file, LogReader $logs, SecurityLog $securityLog): BinaryFileResponse
    {
        $path = $logs->pathFor($file);
        abort_unless($path, 404);

        $securityLog->record('log.downloaded', $request->user(), ['file' => basename($path)], null, 'warning');

        return response()->download($path);
    }
}
