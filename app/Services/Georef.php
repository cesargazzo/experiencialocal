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
     * Normaliza una dirección argentina.
     *
     * 1. La dirección completa ("SAN MARTIN 123, Chilecito, La Rioja") con su punto exacto.
     * 2. Si Georef no tiene la altura de esa calle, normaliza al menos el nombre de la
     *    calle y usa el centro de la localidad (precise = false: hay que ajustar el punto).
     * 3. Si tampoco encuentra la calle, solo el centro de la localidad (normalized = false).
     *
     * @return array{address: string, lat: ?float, lng: ?float, normalized: bool, precise: bool}|null
     */
    public function normalize(string $address, ?string $city = null, ?string $province = null): ?array
    {
        // Georef filtra por localidad con "localidad_censal" (con "localidad" responde error).
        $query = array_filter(['direccion' => $address, 'provincia' => $province, 'localidad_censal' => $city, 'max' => 1]);

        // Con localidad primero; si no aparece, solo con la provincia.
        foreach ([$query, array_diff_key($query, ['localidad_censal' => true])] as $attempt) {
            $found = data_get($this->get('direcciones', $attempt), 'direcciones.0');
            if ($found) {
                $lat = data_get($found, 'ubicacion.lat');
                $lng = data_get($found, 'ubicacion.lon');

                return [
                    'address' => (string) ($found['nomenclatura'] ?? $address),
                    'lat' => $lat !== null ? (float) $lat : null,
                    'lng' => $lng !== null ? (float) $lng : null,
                    'normalized' => true,
                    'precise' => $lat !== null,
                ];
            }
        }

        $center = $city ? $this->locality($city, $province) : null;
        $street = $this->street($address, $city, $province);

        if ($street) {
            return ['address' => $street, 'lat' => $center['lat'] ?? null, 'lng' => $center['lng'] ?? null, 'normalized' => true, 'precise' => false];
        }

        return $center ? ['address' => $address, ...$center, 'normalized' => false, 'precise' => false] : null;
    }

    /**
     * Nombre oficial de la calle, con la altura que escribió la persona:
     * "san martin 123" en Chilecito → "SAN MARTIN 123, Chilecito, La Rioja".
     */
    public function street(string $address, ?string $city = null, ?string $province = null): ?string
    {
        if (! preg_match('/^\s*(?<street>.*?)\s*(?<number>\d+\s*[a-zA-Z]?)?\s*$/u', $address, $parts) || trim($parts['street']) === '') {
            return null;
        }

        $query = array_filter(['nombre' => trim($parts['street']), 'provincia' => $province, 'localidad_censal' => $city, 'max' => 1]);
        $found = data_get($this->get('calles', $query), 'calles.0')
            ?? data_get($this->get('calles', array_diff_key($query, ['localidad_censal' => true])), 'calles.0');

        if (! $found || empty($found['nombre'])) {
            return null;
        }

        $place = array_filter([
            data_get($found, 'localidad_censal.nombre') ?? $city,
            data_get($found, 'provincia.nombre') ?? $province,
        ]);

        return trim($found['nombre'].' '.trim($parts['number'] ?? '')).($place ? ', '.implode(', ', $place) : '');
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
