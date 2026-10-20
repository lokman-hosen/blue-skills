<?php

namespace App\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;
use Spatie\ModelStates\State;
use UnitEnum;

/**
 * A status as StatusChip renders it: the stored value, an English label (translated on the
 * client via `status.{value}` when a key exists) and a design-system tone.
 */
class StatusData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
        /** ok | warn | bad | brand | stamp | neutral */
        public string $tone,
    ) {}

    /**
     * @param  State<Model>  $state
     */
    public static function fromState(State $state): self
    {
        /** @var string $value */
        $value = $state->getValue();

        return new self(
            value: $value,
            label: method_exists($state, 'label') ? $state->label() : $value,
            tone: method_exists($state, 'tone') ? $state->tone() : 'neutral',
        );
    }

    public static function fromEnum(UnitEnum $enum, string $tone = 'neutral'): self
    {
        $value = $enum instanceof \BackedEnum ? (string) $enum->value : $enum->name;

        return new self(
            value: $value,
            label: method_exists($enum, 'label') ? $enum->label() : $value,
            tone: $tone,
        );
    }
}
