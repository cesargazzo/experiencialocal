<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\TeamPermission;
use App\Models\AdvertiserInquiry;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Experience;
use App\Models\Review;
use App\Models\User;
use App\Notifications\AdvertiserInquiryNotification;
use App\Support\CountryList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mediakit para anunciantes: quiénes usan Tinku y cómo se puede anunciar.
 * Los números son totales al día; el público se muestra solo en grupos grandes,
 * nunca de forma que se pueda reconocer a una persona.
 */
class MediaKitController extends Controller
{
    /** Personas mínimas para mostrar cómo se reparte el público. */
    public const MIN_AUDIENCE = 20;

    /** Un grupo más chico que esto se suma en "Otros". */
    public const MIN_GROUP = 5;

    public function show(): View
    {
        $data = Cache::remember('mediakit.stats', now()->addHour(), fn (): array => [
            'numbers' => $this->numbers(),
            'ages' => $this->ages(),
            'origins' => $this->origins(),
            'interests' => $this->interests(),
            'as_of' => now()->toIso8601String(),
        ]);

        return view('pages.mediakit', [
            ...$data,
            'formats' => AdvertiserInquiry::FORMATS,
            'budgets' => AdvertiserInquiry::BUDGETS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Campo trampa: las personas no lo ven; los robots lo completan.
        if ($request->filled('website')) {
            return back()->with('status', __('¡Gracias! Te escribimos en estos días.'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'formats' => ['nullable', 'array'],
            'formats.*' => [Rule::in(array_keys(AdvertiserInquiry::FORMATS))],
            'budget' => ['nullable', Rule::in(array_keys(AdvertiserInquiry::BUDGETS))],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ], [
            'message.min' => __('Contanos un poco más: qué querés comunicar y a quién.'),
        ]);

        $inquiry = AdvertiserInquiry::create([...$data, 'formats' => $data['formats'] ?? null, 'ip' => $request->ip()]);

        $team = User::query()->whereNotNull('team_role')->get()->filter(fn (User $user) => $user->hasTeamPermission(TeamPermission::ManagePlatform));
        Notification::send($team, new AdvertiserInquiryNotification($inquiry));

        return redirect()->to(route('mediakit').'#contacto')->with('status', __('¡Gracias! Te escribimos en estos días.'));
    }

    /** @return array<string, int|float> */
    private function numbers(): array
    {
        $published = Experience::published();

        return [
            'experiences' => (clone $published)->count(),
            'hosts' => (clone $published)->distinct('host_profile_id')->count('host_profile_id'),
            'provinces' => (clone $published)->whereNotNull('province_id')->distinct('province_id')->count('province_id'),
            'people' => User::query()->whereNull('anonymized_at')->count(),
            'visits' => (int) DB::table('experience_daily_views')->where('day', '>=', now()->subDays(30)->toDateString())->sum('views'),
            'favorites' => DB::table('favorites')->count(),
            'bookings' => Booking::query()->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->count(),
            'reviews' => Review::query()->whereNotNull('published_at')->count(),
            'rating' => round((float) Review::query()->whereNotNull('published_at')->avg('rating'), 1),
        ];
    }

    /** @return Collection<int, array{label: string, share: int}>|null */
    private function ages(): ?Collection
    {
        $years = User::query()->whereNull('anonymized_at')->whereNotNull('birth_date')->pluck('birth_date')
            ->map(fn ($date) => $date->age);
        $buckets = ['18 a 24' => [18, 24], '25 a 34' => [25, 34], '35 a 44' => [35, 44], '45 a 54' => [45, 54], '55 o más' => [55, 200]];

        return $this->shares($years->map(fn (int $age) => collect($buckets)->search(fn (array $range) => $age >= $range[0] && $age <= $range[1]) ?: null)->filter(), array_keys($buckets));
    }

    /** @return Collection<int, array{label: string, share: int}>|null */
    private function origins(): ?Collection
    {
        $countries = CountryList::all();
        $codes = User::query()->whereNull('anonymized_at')->whereNotNull('nationality_code')->pluck('nationality_code')
            ->map(fn (string $code) => $countries[$code] ?? $code);

        return $this->shares($codes);
    }

    /** @return Collection<int, array{label: string, share: int}>|null */
    private function interests(): ?Collection
    {
        $names = Category::query()->pluck('name', 'id');
        $picks = DB::table('category_user')->pluck('category_id')->map(fn ($id) => $names[$id] ?? null)->filter();

        return $this->shares($picks);
    }

    /**
     * Porcentaje de cada grupo, de mayor a menor. Sin suficiente gente, null; grupos chicos van a "Otros".
     *
     * @param  Collection<int, string>  $values
     * @param  list<string>|null  $order
     * @return Collection<int, array{label: string, share: int}>|null
     */
    private function shares(Collection $values, ?array $order = null): ?Collection
    {
        $total = $values->count();
        if ($total < self::MIN_AUDIENCE) {
            return null;
        }

        $counts = $values->countBy();
        $others = $counts->filter(fn (int $count) => $count < self::MIN_GROUP)->sum();
        $counts = $counts->filter(fn (int $count) => $count >= self::MIN_GROUP);
        $counts = $order ? collect($order)->mapWithKeys(fn ($key) => [$key => $counts[$key] ?? 0])->filter() : $counts->sortDesc()->take(6);
        if ($others > 0) {
            $counts['Otros'] = $others;
        }

        return $counts->map(fn (int $count, string $label) => ['label' => $label, 'share' => (int) round($count / $total * 100)])->values();
    }
}
