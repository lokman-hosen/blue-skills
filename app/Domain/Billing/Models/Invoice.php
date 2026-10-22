<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\PaymentMethod;
use App\Domain\Billing\States\Invoice\Draft;
use App\Domain\Billing\States\Invoice\InvoiceState;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * A platform invoice to an organization. Issued by a Finance Officer and marked paid when the
 * bank transfer arrives; paying it adds the credits on its lines (D-095). Never deleted: void it.
 *
 * @property InvoiceState $status
 * @property int $subtotal_minor
 * @property int $tax_rate_bp
 * @property int $tax_minor
 * @property int $total_minor
 * @property PaymentMethod|null $payment_method
 * @property Carbon|null $due_on
 * @property Carbon|null $issued_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $voided_at
 */
#[Fillable([
    'number', 'organization_id', 'organization_subscription_id', 'status', 'currency', 'subtotal_minor', 'tax_rate_bp',
    'tax_minor', 'total_minor', 'due_on', 'issued_at', 'paid_at', 'payment_method', 'payment_reference', 'provider',
    'provider_reference', 'notes', 'created_by', 'voided_at',
])]
#[UseFactory(InvoiceFactory::class)]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceState::class,
            'payment_method' => PaymentMethod::class,
            'subtotal_minor' => 'integer',
            'tax_rate_bp' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'due_on' => 'date',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status', 'number', 'total_minor', 'payment_reference'];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<OrganizationSubscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationSubscription::class, 'organization_subscription_id');
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status->equals(Draft::class);
    }

    /**
     * Totals from the lines; VAT is rounded half up on the subtotal.
     */
    public function recalculate(): void
    {
        $subtotal = (int) $this->lines()->sum('amount_minor');
        $tax = intdiv($subtotal * $this->tax_rate_bp + 5_000, 10_000);

        $this->forceFill(['subtotal_minor' => $subtotal, 'tax_minor' => $tax, 'total_minor' => $subtotal + $tax])->save();
    }

    /**
     * INV-2026-00001: year and a running number, given when issued.
     */
    public static function newNumber(): string
    {
        $prefix = 'INV-'.now()->year.'-';
        $last = self::query()->where('number', 'like', $prefix.'%')->max('number');
        $next = $last !== null ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
