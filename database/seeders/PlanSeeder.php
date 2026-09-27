<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'free', 'name' => 'Free', 'tagline' => 'Para probar', 'monthly_price' => 0, 'commission_rate' => 0.18, 'max_experiences' => 1, 'is_featured' => false, 'sort_order' => 1,
                'features' => ['Perfil público', 'Una experiencia activa', 'Reservas y pagos', 'Calificaciones']],
            ['slug' => 'impulso', 'name' => 'Impulso', 'tagline' => 'Para crecer', 'monthly_price' => 19900, 'commission_rate' => 0.12, 'max_experiences' => 5, 'is_featured' => true, 'sort_order' => 2,
                'features' => ['Hasta cinco experiencias', 'Mejor posición en búsquedas', 'Estadísticas', 'Fechas y cupos recurrentes']],
            ['slug' => 'pro', 'name' => 'Pro', 'tagline' => 'Para profesionales', 'monthly_price' => 49900, 'commission_rate' => 0.08, 'max_experiences' => null, 'is_featured' => false, 'sort_order' => 3,
                'features' => ['Experiencias sin límite', 'Calendario y equipo', 'Soporte prioritario', 'Facturación integrada']],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
