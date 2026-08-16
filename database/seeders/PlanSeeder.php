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
                'currency' => 'usd',
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
                'description' => 'Para familias y clubes en crecimiento: sin límites.',
                'price_monthly' => 4900,
                'price_yearly' => 49000,
                'currency' => 'usd',
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
