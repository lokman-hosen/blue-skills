<?php

namespace Database\Factories;

use App\Domain\Identity\Models\User;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory()->worker(),
            'type' => DocumentType::Certificate,
            'title' => fake()->words(3, true),
            'expires_at' => null,
        ];
    }
}
