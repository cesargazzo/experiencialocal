<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\ExperienceDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Calendario del anfitrión: todas las fechas de sus experiencias por mes, con
 * lugares ocupados. Desde acá cierra o reabre la venta, cambia los cupos y saca
 * fechas sin reservas. Las fechas nuevas (sueltas o recurrentes) se cargan en cada experiencia.
 */
#[Layout('components.layout', ['noindex' => true])]
#[Title('Tu calendario')]
class HostCalendar extends Component
{
    #[Url(as: 'mes')]
    public string $month = '';

    public ?int $selectedId = null;

    public ?int $capacity = null;

    public ?string $notice = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hostProfile, 403);
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now(config('tinku.timezone'))->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $this->month = $this->monthStart()->subMonth()->format('Y-m');
        $this->selectedId = null;
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonth()->format('Y-m');
        $this->selectedId = null;
    }

    public function select(int $dateId): void
    {
        $date = $this->ownedDate($dateId);
        $this->selectedId = $date->id;
        $this->capacity = $date->capacity;
        $this->notice = null;
        $this->resetErrorBag();
    }

    public function saveCapacity(): void
    {
        $date = $this->ownedDate((int) $this->selectedId);
        $this->validate(['capacity' => ['required', 'integer', 'min:'.max(1, $date->booked_count), 'max:200']], [
            'capacity.min' => $date->booked_count > 0
                ? "Ya hay {$date->booked_count} lugares reservados: el cupo no puede ser menor."
                : 'Tiene que haber al menos un lugar.',
        ]);

        $date->update(['capacity' => $this->capacity]);
        $this->notice = 'Guardamos el cupo.';
    }

    /** Cierra o reabre la venta de esa fecha. Las reservas ya hechas siguen en pie. */
    public function toggleSales(): void
    {
        $date = $this->ownedDate((int) $this->selectedId);
        if ($date->starts_at->isPast()) {
            return;
        }

        $date->update(['status' => $date->status === 'open' ? 'closed' : 'open']);
        $this->notice = $date->status === 'open' ? 'Volvimos a abrir la venta.' : 'Cerramos la venta: nadie más puede reservar esa fecha.';
    }

    public function remove(): void
    {
        $date = $this->ownedDate((int) $this->selectedId);
        if ($date->booked_count > 0) {
            $this->addError('remove', 'Esa fecha tiene reservas. Cerrá la venta, o escribinos para cancelarla y avisar a quienes reservaron.');

            return;
        }

        $date->delete();
        $this->selectedId = null;
        $this->notice = 'Sacamos la fecha.';
    }

    public function render()
    {
        $start = $this->monthStart();
        $timezone = config('tinku.timezone');
        $dates = $this->datesBetween($start->startOfWeek(), $start->endOfMonth()->endOfWeek());

        $byDay = $dates->groupBy(fn (ExperienceDate $date) => $date->starts_at->timezone($timezone)->toDateString());
        $weeks = collect();
        for ($day = $start->startOfWeek(); $day->lte($start->endOfMonth()->endOfWeek()); $day = $day->addDay()) {
            $weeks->push(['date' => $day, 'inMonth' => $day->month === $start->month, 'isToday' => $day->isToday(), 'items' => $byDay->get($day->toDateString(), collect())]);
        }

        $selected = $this->selectedId ? $dates->firstWhere('id', $this->selectedId) : null;
        $selected?->load(['bookings' => fn ($q) => $q->whereIn('status', [BookingStatus::Requested, BookingStatus::Confirmed, ...BookingStatus::settled()])->with('user')]);

        return view('livewire.host-calendar', [
            'monthLabel' => ucfirst($start->translatedFormat('F Y')),
            'days' => $weeks->chunk(7),
            'selected' => $selected,
            'experiences' => auth()->user()->hostProfile->experiences()->orderBy('title')->get(['id', 'title', 'slug']),
            'weekdayNames' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
        ]);
    }

    private function monthStart(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01', config('tinku.timezone'))->startOfDay();
    }

    /** @return Collection<int, ExperienceDate> */
    private function datesBetween(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return ExperienceDate::query()
            ->whereHas('experience', fn ($q) => $q->where('host_profile_id', auth()->user()->hostProfile->id))
            ->whereBetween('starts_at', [$from->startOfDay()->utc(), $to->endOfDay()->utc()])
            ->with('experience.province')
            ->withCount(['bookings as pending_count' => fn ($q) => $q->where('status', BookingStatus::Requested)])
            ->orderBy('starts_at')
            ->get();
    }

    /** Solo fechas de experiencias propias. */
    private function ownedDate(int $id): ExperienceDate
    {
        return ExperienceDate::query()
            ->whereKey($id)
            ->whereHas('experience', fn ($q) => $q->where('host_profile_id', auth()->user()->hostProfile?->id))
            ->firstOrFail();
    }
}
