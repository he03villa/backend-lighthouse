<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stripe_invoice_id' => $this->stripe_invoice_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'hosted_invoice_url' => $this->hosted_invoice_url,
            'invoice_pdf' => $this->invoice_pdf,
            'due_date' => $this->due_date?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
        ];
    }
}
