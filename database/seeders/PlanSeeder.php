<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Para comenzar: un programa y hasta 10 participantes.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'cop',
                'features' => [
                    '1 programa activo',
                    'Hasta 10 participantes',
                    'Planificación y bitácora',
                ],
                'limits' => [
                    'max_participants' => 10,
                    'max_coaches' => 0,
                    'max_programs' => 1,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Para clubes en crecimiento: hasta 120 participantes y 5 programas.',
                'price_monthly' => 59900,
                'price_yearly' => 599000,
                'currency' => 'cop',
                'features' => [
                    'Hasta 5 programas activos',
                    'Hasta 120 participantes',
                    'Hasta 10 coaches',
                    'Planificación y bitácora',
                ],
                'limits' => [
                    'max_participants' => 120,
                    'max_coaches' => 10,
                    'max_programs' => 5,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Elite',
                'slug' => 'elite',
                'description' => 'Para academias y clubes grandes: sin límites.',
                'price_monthly' => 154900,
                'price_yearly' => 1489000,
                'currency' => 'cop',
                'features' => [
                    'Programas ilimitados',
                    'Hasta 500 participantes',
                    'Hasta 50 coaches',
                    'Planificación y bitácora',
                ],
                'limits' => [
                    'max_participants' => 500,
                    'max_coaches' => 50,
                    'max_programs' => null,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
