<?php

namespace App\Domain\Billing\States\Invoice;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * draft → issued → paid; a draft or issued invoice can be voided. Paid invoices never change.
 *
 * @extends State<Model>
 */
abstract class InvoiceState extends State
{
    abstract public function label(): string;

    /**
     * Design-system tone used by StatusChip: ok, warn, bad, brand, stamp or neutral.
     */
    abstract public function tone(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Issued::class)
            ->allowTransition(Issued::class, Paid::class)
            ->allowTransition(Draft::class, Voided::class)
            ->allowTransition(Issued::class, Voided::class);
    }
}
