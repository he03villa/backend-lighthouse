<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class SyncStripePlans extends Command
{
    protected $signature = 'plans:sync-stripe';

    protected $description = 'Crea los productos y precios de los planes en Stripe y guarda sus IDs.';

    public function handle(StripeClient $stripe): int
    {
        $key = config('cashier.secret');

        if (! $key) {
            $this->error('Falta STRIPE_SECRET en la configuración.');

            return self::FAILURE;
        }

        foreach (Plan::where('is_active', true)->get() as $plan) {
            $product = $stripe->products->create([
                'name' => $plan->name,
                'description' => $plan->description,
                'metadata' => ['lighthouse_plan_id' => $plan->id],
            ]);

            if ($plan->price_monthly > 0) {
                $price = $stripe->prices->create([
                    'product' => $product->id,
                    'unit_amount' => $plan->price_monthly,
                    'currency' => $plan->currency,
                    'recurring' => ['interval' => 'month'],
                    'metadata' => ['lighthouse_plan_id' => $plan->id],
                ]);

                $plan->update(['stripe_price_id_monthly' => $price->id]);
            }

            if ($plan->price_yearly > 0) {
                $price = $stripe->prices->create([
                    'product' => $product->id,
                    'unit_amount' => $plan->price_yearly,
                    'currency' => $plan->currency,
                    'recurring' => ['interval' => 'year'],
                    'metadata' => ['lighthouse_plan_id' => $plan->id],
                ]);

                $plan->update(['stripe_price_id_yearly' => $price->id]);
            }

            $this->info("Plan {$plan->slug} sincronizado (producto {$product->id}).");
        }

        return self::SUCCESS;
    }
}
