<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvertiserInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Consultas de anunciantes que llegan desde el mediakit. */
class AdvertiserInquiryController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('estado'), array_keys(AdvertiserInquiry::STATUSES), true) ? $request->query('estado') : null;

        return view('admin.advertisers', [
            'inquiries' => AdvertiserInquiry::query()->with('handler')->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(30)->withQueryString(),
            'status' => $status,
            'counts' => AdvertiserInquiry::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request, AdvertiserInquiry $inquiry): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(AdvertiserInquiry::STATUSES))]]);
        $inquiry->update(['status' => $data['status'], 'handled_by' => $request->user()->id]);

        return back()->with('status', 'Actualizamos la consulta.');
    }
}
