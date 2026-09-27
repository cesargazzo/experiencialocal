<?php

namespace App\Livewire;

use App\Enums\ExperienceStatus;
use App\Enums\HostStatus;
use App\Enums\VerificationLevel;
use App\Models\Category;
use App\Models\Experience;
use App\Models\HostProfile;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Alta de anfitrión en pasos: perfil, plan, primera experiencia, publicación.
 * La verificación de identidad ya se exigió por middleware para llegar acá.
 */
#[Layout('components.layout')]
#[Title('Registro de anfitrión')]
class HostOnboarding extends Component
{
    public int $step = 1;

    // Paso 1: perfil de anfitrión.
    public string $display_name = '';

    public string $bio = '';

    public string $city = '';

    public string $province = 'La Rioja';

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

        if ($profile = $user->hostProfile) {
            $this->fill($profile->only(['display_name', 'bio', 'city', 'province']));
            $this->plan = $profile->plan->slug;
        }
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'display_name' => 'required|string|max:80',
                'bio' => 'required|string|min:40|max:1000',
                'city' => 'required|string|max:80',
                'province' => 'nullable|string|max:80',
                'address' => 'required|string|max:200',
                'plan' => 'required|exists:plans,slug',
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
            ],
            default => [],
        };
    }

    public function next(): void
    {
        $this->validate($this->rulesForStep($this->step));
        $this->step++;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function publish(): void
    {
        $this->validate($this->rulesForStep(1));
        $this->validate($this->rulesForStep(2));

        $user = auth()->user();
        $plan = Plan::where('slug', $this->plan)->firstOrFail();

        DB::transaction(function () use ($user, $plan) {
            $profile = HostProfile::updateOrCreate(['user_id' => $user->id], [
                'plan_id' => $plan->id,
                'display_name' => $this->display_name,
                'bio' => $this->bio,
                'city' => $this->city,
                'province' => $this->province ?: null,
                'country_code' => $user->country_code ?? 'AR',
                'address' => $this->address,
                // Con nivel 3 el perfil se activa solo; con nivel 2 queda en revisión.
                'status' => $user->hasVerificationLevel(VerificationLevel::Residence) ? HostStatus::Active : HostStatus::InReview,
                'hosting_since' => now(),
                'payout_holder_name' => $user->name,
            ]);

            if (! $profile->canPublishAnother()) {
                $this->addError('title', 'Tu plan no permite más experiencias activas. Cambiá de plan para publicar otra.');
                throw new \RuntimeException('plan limit');
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
                'province' => $this->province ?: null,
                'country_code' => $profile->country_code,
                'price' => $this->price,
                'currency' => 'ARS',
                'duration_minutes' => $this->duration_hours * 60,
                'max_guests' => $this->max_guests,
                'includes' => [],
                'status' => $profile->isActive() ? ExperienceStatus::Published : ExperienceStatus::InReview,
                'published_at' => $profile->isActive() ? now() : null,
            ]);

            $start = Carbon::parse($this->first_date.' '.$this->first_time);
            $this->created->dates()->create([
                'starts_at' => $start,
                'ends_at' => $start->copy()->addHours($this->duration_hours),
                'capacity' => $this->max_guests,
            ]);
        });

        $this->step = 4;
    }

    public function render()
    {
        return view('livewire.host-onboarding', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }
}
