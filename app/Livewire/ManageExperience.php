<?php

namespace App\Livewire;

use App\Enums\ExperienceStatus;
use App\Livewire\Concerns\EditsExperienceDetails;
use App\Models\Category;
use App\Models\Experience;
use App\Rules\ImageSize;
use App\Services\ExperienceModeration;
use App\Services\ImageService;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * El anfitrión edita su experiencia y sus fechas. Si cambia lo que se revisa
 * (textos, categoría o foto) vuelve a revisión; precio, cupos, duración,
 * dificultad, edad mínima, extras y opciones de comida se aplican al momento.
 */
#[Layout('components.layout', ['noindex' => true])]
#[Title('Editá tu experiencia')]
class ManageExperience extends Component
{
    use EditsExperienceDetails;
    use WithFileUploads;

    /** Campos que pasan por la revisión de contenido. */
    private const REVIEWED_FIELDS = ['title', 'category_id', 'type_label', 'summary', 'description', 'what_to_bring'];

    /** Tope de fechas que se crean de una vez. */
    public const MAX_NEW_DATES = 60;

    public Experience $experience;

    public string $title = '';

    public ?int $category_id = null;

    public string $type_label = '';

    public string $summary = '';

    public string $description = '';

    public ?int $price = null;

    public int $duration_hours = 3;

    public ?int $max_guests = null;

    public ?TemporaryUploadedFile $cover = null;

    // Fechas: una sola o varias por días de la semana dentro de un rango.
    public string $date_mode = 'single';

    public string $single_date = '';

    public string $range_from = '';

    public string $range_to = '';

    /** @var list<string> Días ISO: 1 lunes … 7 domingo. */
    public array $weekdays = [];

    public string $time = '20:30';

    /** Aviso del último guardado, visible sin recargar. */
    public ?string $notice = null;

    public function mount(Experience $experience): void
    {
        $this->authorize('update', $experience);

        $this->experience = $experience;
        $this->fill($experience->only(['title', 'category_id', 'type_label', 'summary', 'description', 'max_guests']));
        $this->price = (int) $experience->price;
        $this->duration_hours = max(1, intdiv($experience->duration_minutes, 60));
        $this->fillDetailsFrom($experience);
        $this->single_date = now($experience->timezone())->addWeek()->toDateString();
        $this->range_from = now($experience->timezone())->addDay()->toDateString();
        $this->range_to = now($experience->timezone())->addMonth()->toDateString();
    }

    public function save(ExperienceModeration $moderation, ImageService $images): void
    {
        $this->authorize('update', $this->experience);

        $data = $this->validate([
            'title' => 'required|string|max:120',
            'category_id' => 'required|exists:categories,id',
            'type_label' => 'required|string|max:60',
            'summary' => 'required|string|max:200',
            'description' => 'required|string|min:80',
            'price' => 'required|integer|min:1000',
            'duration_hours' => 'required|integer|min:1|max:24',
            'max_guests' => 'required|integer|min:1|max:50',
            'cover' => ['nullable', ImageSize::forCollection('cover')],
            ...$this->detailRules(),
        ], $this->detailMessages());

        $this->experience->fill([
            ...collect($data)->only(self::REVIEWED_FIELDS)->all(),
            'price' => $data['price'],
            'duration_minutes' => $data['duration_hours'] * 60,
            'max_guests' => $data['max_guests'],
            ...$this->detailAttributes(),
        ]);

        $needsReview = $this->cover !== null
            || $this->experience->isDirty(self::REVIEWED_FIELDS)
            || $this->experience->status === ExperienceStatus::Draft;

        DB::transaction(function () use ($needsReview, $moderation): void {
            $this->experience->save();

            if ($needsReview) {
                $moderation->resubmit($this->experience);
            }
        });

        if ($this->cover) {
            $images->replace($this->experience, 'cover', $this->cover, $this->experience->title);
            $this->cover = null;
        }

        $this->experience->refresh();
        $this->notice = ($needsReview
            ? 'Guardamos los cambios. Como cambiaste el contenido, la revisamos de nuevo antes de publicarla.'
            : 'Guardamos los cambios.');
    }

    public function addDates(): void
    {
        $this->authorize('update', $this->experience);

        $tz = $this->experience->timezone();
        $today = now($tz)->toDateString();

        if ($this->date_mode === 'single') {
            $this->validate([
                'single_date' => ['required', 'date', 'after_or_equal:'.$today],
                'time' => ['required', 'date_format:H:i'],
            ], ['single_date.after_or_equal' => 'Elegí una fecha desde hoy en adelante.']);
            $days = [Carbon::parse($this->single_date, $tz)];
        } else {
            $this->validate([
                'range_from' => ['required', 'date', 'after_or_equal:'.$today],
                'range_to' => ['required', 'date', 'after_or_equal:range_from', 'before_or_equal:'.now($tz)->addYear()->toDateString()],
                'weekdays' => ['required', 'array', 'min:1'],
                'weekdays.*' => ['integer', 'between:1,7'],
                'time' => ['required', 'date_format:H:i'],
            ], [
                'range_from.after_or_equal' => 'El rango tiene que empezar desde hoy en adelante.',
                'range_to.after_or_equal' => 'La fecha final tiene que ser posterior a la inicial.',
                'range_to.before_or_equal' => 'Cargá fechas de hasta un año.',
                'weekdays.required' => 'Elegí al menos un día de la semana.',
            ]);
            $weekdays = array_map('intval', $this->weekdays);
            $days = collect(CarbonPeriod::create(Carbon::parse($this->range_from, $tz), Carbon::parse($this->range_to, $tz)))
                ->filter(fn (Carbon $day) => in_array($day->dayOfWeekIso, $weekdays, true))
                ->values()
                ->all();
        }

        $starts = collect($days)
            ->map(fn (Carbon $day) => Carbon::parse($day->toDateString().' '.$this->time, $tz)->utc())
            ->filter(fn (Carbon $start) => $start->isFuture());

        if ($starts->isEmpty()) {
            $this->addError('dates', 'No hay fechas para cargar con esos datos.');

            return;
        }
        if ($starts->count() > self::MAX_NEW_DATES) {
            $this->addError('dates', 'Son '.$starts->count().' fechas. Cargá hasta '.self::MAX_NEW_DATES.' de una vez.');

            return;
        }

        $existing = $this->experience->dates()->where('status', 'open')->pluck('starts_at')->map->toIso8601String();
        $new = $starts->reject(fn (Carbon $start) => $existing->contains($start->toIso8601String()));

        DB::transaction(function () use ($new): void {
            foreach ($new as $start) {
                $this->experience->dates()->create([
                    'starts_at' => $start,
                    'ends_at' => $start->copy()->addMinutes($this->experience->duration_minutes),
                    'capacity' => $this->experience->max_guests,
                ]);
            }
        });

        $skipped = $starts->count() - $new->count();
        $this->notice = 'Sumamos '.plural_es($new->count(), 'fecha nueva', 'fechas nuevas').($skipped ? " ({$skipped} ya estaban)." : '.');
        $this->resetErrorBag();
    }

    public function removeDate(int $dateId): void
    {
        $this->authorize('update', $this->experience);

        $date = $this->experience->dates()->whereKey($dateId)->firstOrFail();
        if ($date->booked_count > 0) {
            $this->addError('dates', 'Esa fecha ya tiene reservas. Escribinos para cancelarla y avisar a quienes reservaron.');

            return;
        }

        $date->delete();
        $this->notice = 'Sacamos la fecha.';
    }

    public function render()
    {
        return view('livewire.manage-experience', [
            'categories' => Category::orderBy('sort_order')->get(),
            'upcomingDates' => $this->experience->dates()->where('status', 'open')->where('starts_at', '>', now())->get(),
            'weekdayNames' => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'],
        ]);
    }
}
