<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeWebhookController extends WebhookController
{
    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        $response = parent::handleInvoicePaymentSucceeded($payload);

        $this->mirrorInvoice($payload);

        return $response;
    }

    protected function handleInvoicePaymentFailed(array $payload)
    {
        $this->mirrorInvoice($payload);

        return $this->successMethod();
    }

    protected function mirrorInvoice(array $payload): void
    {
        $invoice = $payload['data']['object'] ?? null;

        if (! $invoice || ! isset($invoice['customer'])) {
            return;
        }

        $tenant = Cashier::findBillable($invoice['customer']);

        if (! $tenant) {
            return;
        }

        $amount = $invoice['amount_due'] ?? $invoice['amount_paid'] ?? $invoice['total'] ?? null;

        if ($amount === null) {
            return;
        }

        $subscriptionId = $invoice['subscription'] ?? null;
        $subscription = $subscriptionId
            ? $tenant->subscriptions()->where('stripe_id', $subscriptionId)->first()
            : null;

        $tenant->invoices()->updateOrCreate(
            ['stripe_invoice_id' => $invoice['id']],
            [
                'subscription_id' => $subscription?->id,
                'amount' => (int) $amount,
                'currency' => strtoupper($invoice['currency'] ?? 'usd'),
                'status' => $invoice['status'] ?? 'unknown',
                'hosted_invoice_url' => $invoice['hosted_invoice_url'] ?? null,
                'invoice_pdf' => $invoice['invoice_pdf'] ?? null,
                'due_date' => isset($invoice['due_date']) ? Carbon::createFromTimestamp($invoice['due_date']) : null,
                'paid_at' => isset($invoice['status_paid_at']) ? Carbon::createFromTimestamp($invoice['status_paid_at']) : null,
            ],
        );
    }
}
