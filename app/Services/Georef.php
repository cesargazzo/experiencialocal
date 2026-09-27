<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * API Georef (datos.gob.ar): normaliza direcciones argentinas y devuelve sus
 * coordenadas. Si la API no responde, no rompe nada: devuelve null.
 */
class Georef
{
    /**
     * Dirección normalizada ("SAN MARTIN 123, Chilecito, La Rioja") con sus coordenadas.
     * Si la calle no se encuentra, prueba con el centro de la localidad (sin normalizar).
     *
     * @return array{address: string, lat: ?float, lng: ?float, normalized: bool}|null
     */
    public function normalize(string $address, ?string $city = null, ?string $province = null): ?array
    {
        $query = array_filter(['direccion' => $address, 'provincia' => $province, 'localidad' => $city, 'max' => 1]);

        // Con localidad primero; si no aparece, solo con la provincia.
        foreach ([$query, array_diff_key($query, ['localidad' => true])] as $attempt) {
            $found = data_get($this->get('direcciones', $attempt), 'direcciones.0');
            if ($found) {
                return [
                    'address' => (string) ($found['nomenclatura'] ?? $address),
                    'lat' => isset($found['ubicacion']['lat']) ? (float) $found['ubicacion']['lat'] : null,
                    'lng' => isset($found['ubicacion']['lon']) ? (float) $found['ubicacion']['lon'] : null,
                    'normalized' => true,
                ];
            }
        }

        $center = $city ? $this->locality($city, $province) : null;

        return $center ? ['address' => $address, ...$center, 'normalized' => false] : null;
    }

    /**
     * Centro de una localidad.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function locality(string $city, ?string $province = null): ?array
    {
        $found = data_get($this->get('localidades', array_filter(['nombre' => $city, 'provincia' => $province, 'max' => 1])), 'localidades.0.centroide');

        return isset($found['lat'], $found['lon']) ? ['lat' => (float) $found['lat'], 'lng' => (float) $found['lon']] : null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    private function get(string $endpoint, array $query): ?array
    {
        if (! config('tinku.georef.url')) {
            return null;
        }

        try {
            $response = Http::baseUrl(config('tinku.georef.url'))
                ->timeout(config('tinku.georef.timeout'))
                ->acceptJson()
                ->get($endpoint, $query);

            return $response->successful() ? $response->json() : null;
        } catch (Throwable $e) {
            Log::warning('Georef no respondió', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
