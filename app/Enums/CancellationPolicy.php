<?php

namespace App\Enums;

/**
 * Políticas de cancelación que el anfitrión puede elegir. Son fijas para que
 * quien reserva entienda siempre lo mismo. Si cancela el anfitrión se devuelve
 * todo; si la persona no se presenta, nada.
 */
enum CancellationPolicy: string
{
    case Flexible = 'flexible';
    case Moderate = 'moderate';
    case Strict = 'strict';

    public function label(): string
    {
        return match ($this) {
            self::Flexible => __('Flexible'),
            self::Moderate => __('Moderada'),
            self::Strict => __('Estricta'),
        };
    }

    /**
     * Tramos de devolución: hasta cuántas horas antes y qué porcentaje se devuelve, del más generoso al último.
     *
     * @return list<array{hours: int, percent: int}>
     */
    public function tiers(): array
    {
        return match ($this) {
            self::Flexible => [['hours' => 24, 'percent' => 100]],
            self::Moderate => [['hours' => 72, 'percent' => 100], ['hours' => 24, 'percent' => 50]],
            self::Strict => [['hours' => 168, 'percent' => 100], ['hours' => 72, 'percent' => 50]],
        };
    }

    /** Qué porcentaje se devuelve si quien reservó cancela faltando esas horas. */
    public function refundPercent(float $hoursBeforeStart): int
    {
        foreach ($this->tiers() as $tier) {
            if ($hoursBeforeStart >= $tier['hours']) {
                return $tier['percent'];
            }
        }

        return 0;
    }

    /**
     * La política en frases cortas.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        $lines = [];
        foreach ($this->tiers() as $tier) {
            $lines[] = $tier['percent'] === 100
                ? __('Sin costo hasta :time antes.', ['time' => self::duration($tier['hours'])])
                : __('Se devuelve el :percent% hasta :time antes.', ['percent' => $tier['percent'], 'time' => self::duration($tier['hours'])]);
        }
        $lines[] = __('Después, o si no te presentás, no hay devolución.');

        return $lines;
    }

    public function summary(): string
    {
        return implode(' ', $this->lines());
    }

    private static function duration(int $hours): string
    {
        return $hours % 24 === 0 && $hours > 24
            ? __(':count días', ['count' => intdiv($hours, 24)])
            : __(':count horas', ['count' => $hours]);
    }
}
