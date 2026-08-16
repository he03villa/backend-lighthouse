<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Subscription;

#[Fillable(['tenant_id', 'subscription_id', 'stripe_invoice_id', 'amount', 'currency', 'status', 'hosted_invoice_url', 'invoice_pdf', 'due_date', 'paid_at'])]
class Invoice extends Model
{
    use BelongsToTenant, HasUuids;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_date' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }
}
