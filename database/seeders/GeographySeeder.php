<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Province;
use Illuminate\Database\Seeder;

/**
 * Datos de referencia: países habilitados y sus provincias con la zona
 * horaria oficial (IANA) de cada una. Es idempotente: para sumar un país,
 * agregalo acá y corré `php artisan db:seed --class=GeographySeeder`.
 */
class GeographySeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, currency: string, timezone: string, phone_prefix: string, active: bool, provinces: list<array{0: string, 1: string, 2: string}>}>
     */
    public const COUNTRIES = [
        'AR' => [
            'name' => 'Argentina',
            'currency' => 'ARS',
            'timezone' => 'America/Argentina/Buenos_Aires',
            'phone_prefix' => '+54',
            'active' => true,
            // [código ISO 3166-2, nombre, zona horaria]
            'provinces' => [
                ['AR-C', 'Ciudad Autónoma de Buenos Aires', 'America/Argentina/Buenos_Aires'],
                ['AR-B', 'Buenos Aires', 'America/Argentina/Buenos_Aires'],
                ['AR-K', 'Catamarca', 'America/Argentina/Catamarca'],
                ['AR-H', 'Chaco', 'America/Argentina/Cordoba'],
                ['AR-U', 'Chubut', 'America/Argentina/Catamarca'],
                ['AR-X', 'Córdoba', 'America/Argentina/Cordoba'],
                ['AR-W', 'Corrientes', 'America/Argentina/Cordoba'],
                ['AR-E', 'Entre Ríos', 'America/Argentina/Cordoba'],
                ['AR-P', 'Formosa', 'America/Argentina/Cordoba'],
                ['AR-Y', 'Jujuy', 'America/Argentina/Jujuy'],
                ['AR-L', 'La Pampa', 'America/Argentina/Salta'],
                ['AR-F', 'La Rioja', 'America/Argentina/La_Rioja'],
                ['AR-M', 'Mendoza', 'America/Argentina/Mendoza'],
                ['AR-N', 'Misiones', 'America/Argentina/Cordoba'],
                ['AR-Q', 'Neuquén', 'America/Argentina/Salta'],
                ['AR-R', 'Río Negro', 'America/Argentina/Salta'],
                ['AR-A', 'Salta', 'America/Argentina/Salta'],
                ['AR-J', 'San Juan', 'America/Argentina/San_Juan'],
                ['AR-D', 'San Luis', 'America/Argentina/San_Luis'],
                ['AR-Z', 'Santa Cruz', 'America/Argentina/Rio_Gallegos'],
                ['AR-S', 'Santa Fe', 'America/Argentina/Cordoba'],
                ['AR-G', 'Santiago del Estero', 'America/Argentina/Cordoba'],
                ['AR-V', 'Tierra del Fuego, Antártida e Islas del Atlántico Sur', 'America/Argentina/Ushuaia'],
                ['AR-T', 'Tucumán', 'America/Argentina/Tucuman'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::COUNTRIES as $code => $country) {
            Country::query()->updateOrCreate(['code' => $code], [
                'name' => $country['name'],
                'currency' => $country['currency'],
                'default_timezone' => $country['timezone'],
                'phone_prefix' => $country['phone_prefix'],
                'is_active' => $country['active'],
            ]);

            foreach ($country['provinces'] as [$provinceCode, $name, $timezone]) {
                Province::query()->updateOrCreate(['code' => $provinceCode], [
                    'country_code' => $code,
                    'name' => $name,
                    'timezone' => $timezone,
                ]);
            }
        }
    }
}
