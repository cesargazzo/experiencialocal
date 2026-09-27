<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\ExperienceStatus;
use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Models\Review;
use App\Services\ExperienceModeration;
use App\Services\Georef;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    private const NORMALIZE_BATCH = 30;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', Rule::enum(ExperienceStatus::class)],
        ]);

        $inReview = Experience::query()
            ->with(['host.user', 'category', 'province', 'cover', 'latestCoverUpload'])
            ->where('status', ExperienceStatus::InReview)
            ->oldest('updated_at')
            ->get();

        $all = Experience::query()
            ->with(['host.user', 'province', 'upcomingDates'])
            ->withCount([
                'bookings',
                'bookings as upcoming_bookings_count' => fn ($q) => $q
                    ->whereIn('status', [BookingStatus::Requested, BookingStatus::Confirmed])
                    ->whereHas('date', fn ($d) => $d->where('starts_at', '>', now())),
            ])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('title', 'ilike', "%{$term}%")
                ->orWhereHas('host', fn ($h) => $h->where('display_name', 'ilike', "%{$term}%"))))
            ->when($filters['estado'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.experiences', [
            'pending' => $inReview->whereNull('approved_at'),
            'awaitingHost' => $inReview->whereNotNull('approved_at'),
            'all' => $all,
            'filters' => $filters,
            'statuses' => ExperienceStatus::cases(),
            'pendingAddresses' => Experience::query()->whereNotNull('meeting_address')->whereNull('address_normalized_at')->count(),
        ]);
    }

    public function show(Experience $experience): View
    {
        $experience->load(['host.user', 'category', 'province', 'cover', 'latestCoverUpload', 'approver', 'upcomingDates']);

        return view('admin.experience', [
            'experience' => $experience,
            'bookings' => $experience->bookings()->with(['user', 'date'])->latest()->limit(50)->get(),
            'reviews' => Review::query()->with('user')->where('experience_id', $experience->id)->latest()->get(),
        ]);
    }

    /** Normaliza con Georef la dirección del punto de encuentro de una experiencia. */
    public function normalizeAddress(Experience $experience, Georef $georef): RedirectResponse
    {
        if (! $experience->meeting_address) {
            return back()->withErrors(['address' => 'Esta experiencia no tiene dirección cargada.']);
        }

        $result = $this->normalize($experience, $georef);

        return $result === 'normalized'
            ? back()->with('status', "Dirección normalizada: {$experience->meeting_address}.")
            : back()->withErrors(['address' => $result === 'located'
                ? 'Georef no encontró la calle; ubicamos la experiencia en el centro de la localidad.'
                : 'Georef no encontró la dirección o no respondió. Probá más tarde o corregila a mano.']);
    }

    /** Normaliza las direcciones pendientes, de a tandas para no saturar la API. */
    public function normalizePending(Georef $georef): RedirectResponse
    {
        $pending = Experience::query()->with('province')->whereNotNull('meeting_address')->whereNull('address_normalized_at')->oldest('id')->limit(self::NORMALIZE_BATCH)->get();
        $results = $pending->map(fn (Experience $experience) => $this->normalize($experience, $georef))->countBy();

        return back()->with('status', sprintf(
            'Revisamos %d direcciones: %d normalizadas, %d ubicadas solo por localidad, %d sin encontrar.',
            $pending->count(), $results['normalized'] ?? 0, $results['located'] ?? 0, $results['missing'] ?? 0,
        ));
    }

    public function pause(Request $request, Experience $experience, ExperienceModeration $moderation): RedirectResponse
    {
        abort_if($experience->status === ExperienceStatus::Paused, 409, 'La experiencia ya está pausada.');

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Escribí el motivo: se lo mandamos al anfitrión.']);
        $moderation->pause($experience, $data['reason'], $request->user());

        return back()->with('status', "Pausamos {$experience->title}.");
    }

    public function resume(Request $request, Experience $experience, ExperienceModeration $moderation): RedirectResponse
    {
        abort_unless($experience->status === ExperienceStatus::Paused, 409, 'La experiencia no está pausada.');

        $moderation->resume($experience, $request->user());

        return back()->with('status', "Reactivamos {$experience->title}.");
    }

    public function approve(Request $request, Experience $experience, ExperienceModeration $moderation): RedirectResponse
    {
        abort_unless($experience->status === ExperienceStatus::InReview, 409, 'La experiencia ya no está en revisión.');

        $moderation->approve($experience, $request->user());

        return back()->with('status', $experience->status === ExperienceStatus::Published
            ? "Publicamos {$experience->title}."
            : "Aprobada. {$experience->title} se publica cuando el anfitrión valide su domicilio.");
    }

    public function reject(Request $request, Experience $experience, ExperienceModeration $moderation): RedirectResponse
    {
        abort_unless($experience->status === ExperienceStatus::InReview, 409, 'La experiencia ya no está en revisión.');

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Escribí el motivo: se lo mandamos al anfitrión.']);
        $moderation->reject($experience, $data['reason'], $request->user());

        return back()->with('status', "Devolvimos {$experience->title} al anfitrión con el motivo.");
    }

    /** @return 'normalized'|'located'|'missing' */
    private function normalize(Experience $experience, Georef $georef): string
    {
        $found = $georef->normalize($experience->meeting_address, $experience->city, $experience->province?->name);
        if (! $found || $found['lat'] === null) {
            return 'missing';
        }

        $experience->update([
            'meeting_address' => $found['normalized'] ? $found['address'] : $experience->meeting_address,
            // Si la ubicación la marcó el anfitrión a mano, se respeta; solo se completa si falta.
            'latitude' => $experience->hasLocation() && ! $found['normalized'] ? $experience->latitude : $found['lat'],
            'longitude' => $experience->hasLocation() && ! $found['normalized'] ? $experience->longitude : $found['lng'],
            'address_normalized_at' => $found['normalized'] ? now() : null,
        ]);

        return $found['normalized'] ? 'normalized' : 'located';
    }
}
