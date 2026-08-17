<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
use Stripe\Product;
use Stripe\StripeClient;

class SyncStripePlans extends Command
{
    protected $signature = 'plans:sync-stripe';

    protected $description = 'Crea los productos y precios de los planes en Stripe y guarda sus IDs.';

    public function handle(): int
    {
        if (! config('cashier.secret')) {
            $this->error('Falta STRIPE_SECRET en la configuración.');

            return self::FAILURE;
        }

        $stripe = Cashier::stripe();

        foreach (Plan::where('is_active', true)->get() as $plan) {
            $product = $this->findOrCreateProduct($stripe, $plan);

            $this->syncPrice($stripe, $plan, $product, 'month', $plan->price_monthly, 'stripe_price_id_monthly');
            $this->syncPrice($stripe, $plan, $product, 'year', $plan->price_yearly, 'stripe_price_id_yearly');

            $this->info("Plan {$plan->slug} sincronizado (producto {$product->id}).");
        }

        return self::SUCCESS;
    }

    private function findOrCreateProduct(StripeClient $stripe, Plan $plan): Product
    {
        $product = collect($stripe->products->all(['active' => true, 'limit' => 100])->data)
            ->first(fn ($item) => ($item->metadata['lighthouse_plan_id'] ?? null) === $plan->id);

        if ($product) {
            if ($product->name !== $plan->name || $product->description !== $plan->description) {
                return $stripe->products->update($product->id, [
                    'name' => $plan->name,
                    'description' => $plan->description,
                ]);
            }

            return $product;
        }

        return $stripe->products->create([
            'name' => $plan->name,
            'description' => $plan->description,
            'metadata' => ['lighthouse_plan_id' => $plan->id],
        ]);
    }

    private function syncPrice(StripeClient $stripe, Plan $plan, Product $product, string $interval, int $amount, string $column): void
    {
        if ($amount <= 0) {
            return;
        }

        $minorAmount = $this->minorUnit($amount, $plan->currency);
        $priceId = $plan->{$column};

        if ($priceId && $this->matches($stripe, $priceId, $minorAmount, $plan->currency)) {
            return;
        }

        if ($priceId) {
            $stripe->prices->update($priceId, ['active' => false]);
        }

        $price = $stripe->prices->create([
            'product' => $product->id,
            'unit_amount' => $minorAmount,
            'currency' => $plan->currency,
            'recurring' => ['interval' => $interval],
            'metadata' => ['lighthouse_plan_id' => $plan->id],
        ]);

        $plan->update([$column => $price->id]);
    }

    private function matches(StripeClient $stripe, string $priceId, int $amount, string $currency): bool
    {
        $price = $stripe->prices->retrieve($priceId);

        return $price->unit_amount === $amount && $price->currency === $currency;
    }

    private function minorUnit(int $amount, string $currency): int
    {
        if (in_array($currency, ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'], true)) {
            return $amount;
        }

        return $amount * 100;
    }
}
