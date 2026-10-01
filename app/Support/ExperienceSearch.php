<?php

namespace App\Support;

use App\Enums\DietaryOption;
use App\Enums\Difficulty;
use App\Enums\ExperienceFeature;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Filtros del buscador de experiencias. Lo que llega mal en la URL se ignora
 * en lugar de dar error: es una búsqueda, no un formulario.
 */
class ExperienceSearch
{
    /** @var array<string, string> */
    public const SORTS = [
        'recomendadas' => 'Recomendadas',
        'proximas' => 'Fecha más próxima',
        'precio' => 'Menor precio',
        'precio_desc' => 'Mayor precio',
        'puntaje' => 'Mejor puntuadas',
    ];

    /** @var array<string, mixed> */
    public readonly array $filters;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(array $input)
    {
        $validator = Validator::make($input, [
            'lugar' => ['string', 'max:80'],
            'fecha' => ['date_format:Y-m-d', 'after_or_equal:today'],
            'personas' => ['integer', 'between:1,50'],
            'precio_max' => ['integer', 'min:1'],
            'comida' => ['array'],
            'comida.*' => [Rule::enum(DietaryOption::class)],
            'necesito' => ['array'],
            'necesito.*' => [Rule::enum(ExperienceFeature::class)],
            'dificultad' => ['array'],
            'dificultad.*' => [Rule::enum(Difficulty::class)],
            'orden' => [Rule::in(array_keys(self::SORTS))],
        ]);

        // Solo lo válido; un valor malo dentro de una lista descarta la lista entera.
        $valid = $validator->valid();
        foreach (['comida', 'necesito', 'dificultad'] as $list) {
            if (isset($valid[$list]) && $validator->errors()->hasAny([$list, "{$list}.*"])) {
                unset($valid[$list]);
            }
        }

        $this->filters = array_filter($valid, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    public function apply(Builder $query): Builder
    {
        $filters = $this->filters;
        $seats = (int) ($filters['personas'] ?? 1);

        $query
            ->when($filters['lugar'] ?? null, function (Builder $query, string $place) {
                $like = '%'.trim($place).'%';
                $query->where(fn ($q) => $q->where('city', 'ilike', $like)->orWhereHas('province', fn ($p) => $p->where('name', 'ilike', $like)));
            })
            ->when(isset($filters['personas']), fn (Builder $q) => $q->where('max_guests', '>=', $seats))
            ->when($filters['precio_max'] ?? null, fn (Builder $q, int|string $price) => $q->where('price', '<=', (int) $price))
            ->when($filters['dificultad'] ?? null, fn (Builder $q, array $levels) => $q->whereIn('difficulty', $levels));

        // Una fecha con lugar para todas las personas: ese día, o cualquier día próximo si no eligió fecha.
        if (isset($filters['fecha']) || isset($filters['personas'])) {
            $query->whereHas('dates', function (Builder $dates) use ($filters, $seats) {
                $dates->where('status', 'open')->where('starts_at', '>', now())->whereRaw('capacity - booked_count >= ?', [$seats]);
                if (isset($filters['fecha'])) {
                    $day = CarbonImmutable::parse($filters['fecha'], config('tinku.timezone'));
                    $dates->whereBetween('starts_at', [$day->startOfDay()->utc(), $day->endOfDay()->utc()]);
                }
            });
        }

        // Cada necesidad de comida tiene que estar cubierta (vegano cubre vegetariano, sin TACC cubre sin gluten).
        foreach ($filters['comida'] ?? [] as $need) {
            $offers = array_map(fn (DietaryOption $option) => $option->value, DietaryOption::from($need)->coveringOptions());
            $query->where(fn (Builder $q) => collect($offers)->each(fn (string $offer) => $q->orWhereJsonContains('dietary_options', $offer)));
        }
        foreach ($filters['necesito'] ?? [] as $feature) {
            $query->whereJsonContains('features', $feature);
        }

        return match ($filters['orden'] ?? 'recomendadas') {
            'precio' => $query->orderBy('price'),
            'precio_desc' => $query->orderByDesc('price'),
            'puntaje' => $query->orderByRaw('reviews_count = 0')->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
            'proximas' => $query->orderByRaw("(select min(starts_at) from experience_dates where experience_dates.experience_id = experiences.id and status = 'open' and starts_at > now()) asc nulls last"),
            default => $query->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
        };
    }

    /** Cuántos filtros del panel "Más filtros" están activos (sin contar categoría, lugar ni orden). */
    public function extraFilterCount(): int
    {
        return count(array_intersect_key($this->filters, array_flip(['precio_max', 'comida', 'necesito', 'dificultad'])));
    }

    /** @return list<string> */
    public function selected(string $list): array
    {
        return array_values($this->filters[$list] ?? []);
    }
}
