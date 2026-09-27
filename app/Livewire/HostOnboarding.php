<?php

namespace App\Livewire;

use App\Enums\ExperienceStatus;
use App\Enums\HostStatus;
use App\Enums\VerificationLevel;
use App\Livewire\Concerns\EditsExperienceDetails;
use App\Models\Category;
use App\Models\Country;
use App\Models\Experience;
use App\Models\HostProfile;
use App\Models\Plan;
use App\Models\Province;
use App\Rules\ImageSize;
use App\Services\Georef;
use App\Services\ImageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Alta de anfitrión en pasos: perfil, plan, primera experiencia, envío a revisión.
 * La verificación de identidad ya se exigió por middleware para llegar acá.
 */
#[Layout('components.layout', ['noindex' => true])]
#[Title('Registro de anfitrión')]
class HostOnboarding extends Component
{
    use EditsExperienceDetails;
    use WithFileUploads;

    /** Foto de perfil opcional; si ya tiene una, se conserva. */
    public ?TemporaryUploadedFile $avatar = null;

    /** Foto de la experiencia: obligatoria, porque la marca pide fotos reales. */
    public ?TemporaryUploadedFile $cover = null;

    public int $step = 1;

    // Paso 1: perfil de anfitrión.
    public string $display_name = '';

    public string $bio = '';

    public string $city = '';

    public ?int $province_id = null;

    public string $address = '';

    public string $plan = 'free';

    // Paso 2: primera experiencia.
    public string $title = '';

    public ?int $category_id = null;

    public string $type_label = '';

    public string $summary = '';

    public string $description = '';

    public ?int $price = null;

    public int $duration_hours = 3;

    public ?int $max_guests = null;

    public string $first_date = '';

    public string $first_time = '20:30';

    public ?Experience $created = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->display_name = $user->name;
        $this->plan = request()->query('plan', 'free');
        $this->first_date = now()->addWeek()->toDateString();

        $this->city = $user->city ?? '';
        $this->province_id = $user->province_id;

        // Quien ya es anfitrión arranca directo en la nueva experiencia; puede volver a editar su perfil.
        if ($profile = $user->hostProfile) {
            $this->fill(array_filter($profile->only(['display_name', 'bio', 'city', 'province_id', 'address']), fn ($value) => $value !== null));
            $this->plan = $profile->plan->slug;
            $this->step = 2;
        }
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'display_name' => 'required|string|max:80',
                'bio' => 'required|string|min:40|max:1000',
                'city' => 'required|string|max:80',
                'province_id' => ['required', Rule::exists('provinces', 'id')->whereIn('country_code', Country::active()->pluck('code')->all())],
                'address' => 'required|string|max:200',
                'plan' => 'required|exists:plans,slug',
                'avatar' => ['nullable', ...$this->imageRules('avatar')],
            ],
            2 => [
                'title' => 'required|string|max:120',
                'category_id' => 'required|exists:categories,id',
                'type_label' => 'required|string|max:60',
                'summary' => 'required|string|max:200',
                'description' => 'required|string|min:80',
                'price' => 'required|integer|min:1000',
                'duration_hours' => 'required|integer|min:1|max:24',
                'max_guests' => 'required|integer|min:1|max:50',
                'first_date' => 'required|date|after:today',
                'first_time' => 'required|date_format:H:i',
                'cover' => ['required', ...$this->imageRules('cover')],
                ...$this->detailRules(),
            ],
            default => [],
        };
    }

    /**
     * @return list<mixed>
     */
    private function imageRules(string $collection): array
    {
        return [ImageSize::forCollection($collection)];
    }

    public function next(): void
    {
        $this->validate($this->rulesForStep($this->step), $this->detailMessages());
        $this->step++;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function publish(): void
    {
        $this->validate($this->rulesForStep(1));
        $this->validate($this->rulesForStep(2), $this->detailMessages());

        $user = auth()->user();
        $plan = Plan::where('slug', $this->plan)->firstOrFail();

        $province = Province::query()->findOrFail($this->province_id);

        $published = DB::transaction(function () use ($user, $plan, $province): bool {
            $profile = HostProfile::updateOrCreate(['user_id' => $user->id], [
                'plan_id' => $plan->id,
                'display_name' => $this->display_name,
                'bio' => $this->bio,
                'city' => $this->city,
                'province_id' => $province->id,
                'country_code' => $province->country_code,
                'address' => $this->address,
                // Con nivel 3 el perfil se activa solo; con nivel 2 queda en revisión.
                'status' => $user->hasVerificationLevel(VerificationLevel::Residence) ? HostStatus::Active : HostStatus::InReview,
                'hosting_since' => $user->hostProfile?->hosting_since ?? now(),
                'payout_holder_name' => $user->name,
            ]);

            if (! $profile->canPublishAnother()) {
                return false;
            }

            $slug = Str::slug($this->title);
            $base = $slug;
            for ($i = 2; Experience::where('slug', $slug)->exists(); $i++) {
                $slug = "$base-$i";
            }

            $this->created = $profile->experiences()->create([
                'category_id' => $this->category_id,
                'title' => $this->title,
                'slug' => $slug,
                'type_label' => $this->type_label,
                'summary' => $this->summary,
                'description' => $this->description,
                'city' => $this->city,
                'province_id' => $province->id,
                'country_code' => $province->country_code,
                'price' => $this->price,
                'currency' => $province->country->currency,
                'duration_minutes' => $this->duration_hours * 60,
                'max_guests' => $this->max_guests,
                'includes' => [],
                ...$this->detailAttributes(),
                // Toda experiencia nueva pasa por la revisión de contenido antes de publicarse.
                'status' => ExperienceStatus::InReview,
                'published_at' => null,
            ]);

            // La fecha se carga en la hora del lugar y se guarda en UTC.
            $start = Carbon::parse($this->first_date.' '.$this->first_time, $province->timezone)->utc();
            $this->created->dates()->create([
                'starts_at' => $start,
                'ends_at' => $start->copy()->addHours($this->duration_hours),
                'capacity' => $this->max_guests,
            ]);

            return true;
        });

        if (! $published) {
            $this->addError('title', 'Tu plan no permite más experiencias activas. Cambiá de plan para publicar otra.');

            return;
        }

        // El punto de encuentro arranca en el domicilio del anfitrión; si Georef lo encuentra, queda normalizado y en el mapa.
        $this->created->forceFill(['meeting_address' => $this->address])->save();
        $found = app(Georef::class)->normalize($this->address, $this->city, $province->name);
        if ($found && $found['lat'] !== null) {
            // Si el punto es solo el centro de la localidad, el anfitrión lo ajusta después.
            $this->created->forceFill([
                'meeting_address' => $found['normalized'] ? $found['address'] : $this->address,
                'latitude' => $found['lat'],
                'longitude' => $found['lng'],
                'address_normalized_at' => $found['normalized'] ? now() : null,
            ])->save();
        }

        $images = app(ImageService::class);
        $images->replace($this->created, 'cover', $this->cover, $this->title);
        if ($this->avatar) {
            $images->replace($user, 'avatar', $this->avatar, $user->name);
        }

        $this->step = 4;
    }

    public function render()
    {
        return view('livewire.host-onboarding', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => Category::orderBy('sort_order')->get(),
            'countries' => Country::active()->with('provinces')->orderBy('name')->get(),
        ]);
    }
}
