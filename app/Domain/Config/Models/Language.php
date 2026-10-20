<?php

namespace App\Domain\Config\Models;

use App\Domain\Config\Enums\LanguageStatus;
use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'native_name', 'english_name', 'rtl', 'status'])]
#[RouteKey('code')]
#[UseFactory(LanguageFactory::class)]
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rtl' => 'boolean',
            'status' => LanguageStatus::class,
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', LanguageStatus::Active);
    }
}
